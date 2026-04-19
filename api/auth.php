<?php
/**
 * api/auth.php
 *
 * Handles user authentication via AJAX.
 * Actions: register | login | logout | check
 * All responses are JSON.
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/WorkspaceModel.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    // ----------------------------------------------------------
    // Register a new user and create a default workspace
    // ----------------------------------------------------------
    case 'register':
        $data     = json_decode(file_get_contents('php://input'), true) ?? [];
        $name     = trim($data['name']     ?? '');
        $email    = trim($data['email']    ?? '');
        $password =       $data['password'] ?? '';

        if (!$name || !$email || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'All fields are required.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email address.']);
            exit;
        }

        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 6 characters.']);
            exit;
        }

        if (UserModel::findByEmail($email)) {
            http_response_code(409);
            echo json_encode(['error' => 'An account with this email already exists.']);
            exit;
        }

        $userId    = UserModel::register($name, $email, $password);
        $workspace = WorkspaceModel::create($userId, $name . "'s Workspace");

        $_SESSION['user_id']      = $userId;
        $_SESSION['user_name']    = $name;
        $_SESSION['workspace_id'] = $workspace['id'];

        echo json_encode([
            'success'  => true,
            'redirect' => '/hagglenote/views/editor.php',
        ]);
        break;

    // ----------------------------------------------------------
    // Login an existing user
    // ----------------------------------------------------------
    case 'login':
        $data     = json_decode(file_get_contents('php://input'), true) ?? [];
        $email    = trim($data['email']    ?? '');
        $password =       $data['password'] ?? '';

        if (!$email || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password are required.']);
            exit;
        }

        $user = UserModel::login($email, $password);

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid email or password.']);
            exit;
        }

        $workspaces = WorkspaceModel::listByUser($user['id']);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];

        if (!empty($workspaces)) {
            $_SESSION['workspace_id'] = $workspaces[0]['id'];
        }

        echo json_encode([
            'success'  => true,
            'redirect' => '/hagglenote/views/editor.php',
        ]);
        break;

    // ----------------------------------------------------------
    // Logout the current user
    // ----------------------------------------------------------
    case 'logout':
        session_destroy();
        echo json_encode([
            'success'  => true,
            'redirect' => '/hagglenote/views/index.php',
        ]);
        break;

    // ----------------------------------------------------------
    // Check current session status (used by JS on page load)
    // ----------------------------------------------------------
    case 'check':
        if (isset($_SESSION['user_id'])) {
            echo json_encode([
                'authenticated' => true,
                'user' => [
                    'id'   => $_SESSION['user_id'],
                    'name' => $_SESSION['user_name'],
                ],
            ]);
        } else {
            echo json_encode(['authenticated' => false]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action.']);
}
