<?php
/**
 * api/workspace.php
 *
 * Workspace CRUD via AJAX.
 * Actions: create | list | delete
 * All responses are JSON. Requires active session.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

require_once __DIR__ . '/../models/WorkspaceModel.php';

$action = $_GET['action'] ?? '';
$userId = (int) $_SESSION['user_id'];

switch ($action) {

    // ----------------------------------------------------------
    // Create a new workspace
    // ----------------------------------------------------------
    case 'create':
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($data['name'] ?? '');

        if (!$name) {
            http_response_code(400);
            echo json_encode(['error' => 'Workspace name is required.']);
            exit;
        }

        $workspace = WorkspaceModel::create($userId, $name);

        echo json_encode([
            'success'   => true,
            'workspace' => $workspace,
        ]);
        break;

    // ----------------------------------------------------------
    // List all workspaces for the logged-in user
    // ----------------------------------------------------------
    case 'list':
        $workspaces = WorkspaceModel::listByUser($userId);
        echo json_encode(['workspaces' => $workspaces]);
        break;

    // ----------------------------------------------------------
    // Delete a workspace (user must own it)
    // ----------------------------------------------------------
    case 'delete':
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int) ($data['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Workspace ID is required.']);
            exit;
        }

        $deleted = WorkspaceModel::delete($id, $userId);

        echo json_encode(['success' => $deleted]);
        break;

    // ----------------------------------------------------------
    // Rename a workspace (user must own it)
    // ----------------------------------------------------------
    case 'rename':
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int) ($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');

        if (!$id || !$name) {
            http_response_code(400);
            echo json_encode(['error' => 'id and name required.']);
            exit;
        }

        $renamed = WorkspaceModel::rename($id, $userId, $name);
        echo json_encode(['success' => $renamed]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action.']);
}
