<?php
/**
 * api/page.php
 *
 * Page CRUD via AJAX.
 * Actions: create | list | get | update | delete
 * All responses are JSON. Requires active session.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

require_once __DIR__ . '/../models/PageModel.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    // ----------------------------------------------------------
    // Create a new page (top-level or nested under a parent)
    // ----------------------------------------------------------
    case 'create':
        $data        = json_decode(file_get_contents('php://input'), true) ?? [];
        $workspaceId = (int) ($data['workspace_id'] ?? 0);
        $parentId    = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        $sectionId   = !empty($data['section_id']) ? $data['section_id'] : null;
        $title       = trim($data['title'] ?? 'Untitled');

        if (!$workspaceId) {
            http_response_code(400);
            echo json_encode(['error' => 'Workspace ID is required.']);
            exit;
        }

        $page = PageModel::create($workspaceId, $title, $parentId, $sectionId);

        echo json_encode([
            'success' => true,
            'page'    => $page,
        ]);
        break;

    // ----------------------------------------------------------
    // List all pages in a workspace (flat — tree built in JS)
    // ----------------------------------------------------------
    case 'list':
        $workspaceId = (int) ($_GET['workspace_id'] ?? 0);

        if (!$workspaceId) {
            http_response_code(400);
            echo json_encode(['error' => 'Workspace ID is required.']);
            exit;
        }

        $pages = PageModel::listByWorkspace($workspaceId);
        echo json_encode(['pages' => $pages]);
        break;

    // ----------------------------------------------------------
    // Get a single page with all its blocks and checklist items
    // ----------------------------------------------------------
    case 'get':
        $id = (int) ($_GET['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Page ID is required.']);
            exit;
        }

        $page = PageModel::getWithBlocks($id);

        if (!$page) {
            http_response_code(404);
            echo json_encode(['error' => 'Page not found.']);
            exit;
        }

        echo json_encode(['page' => $page]);
        break;

    // ----------------------------------------------------------
    // Update a page's title
    // ----------------------------------------------------------
    case 'update':
        $data  = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = (int) ($data['id']    ?? 0);
        $title = trim($data['title']   ?? '');

        if (!$id || !$title) {
            http_response_code(400);
            echo json_encode(['error' => 'Page ID and title are required.']);
            exit;
        }

        PageModel::update($id, $title);
        echo json_encode(['success' => true]);
        break;

    // ----------------------------------------------------------
    // Delete a page (cascades to child pages + blocks via FK)
    // ----------------------------------------------------------
    case 'delete':
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int) ($data['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Page ID is required.']);
            exit;
        }

        PageModel::delete($id);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action.']);
}
