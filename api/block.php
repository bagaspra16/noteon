<?php
/**
 * api/block.php
 *
 * Block and checklist item CRUD via AJAX.
 * Actions: create | update | delete | reorder
 *          add_checklist_item | update_checklist_item | toggle_item | delete_checklist_item
 * All responses are JSON. Requires active session.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

require_once __DIR__ . '/../models/BlockModel.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    // ----------------------------------------------------------
    // Create a new block in a page
    // ----------------------------------------------------------
    case 'create':
        $data     = json_decode(file_get_contents('php://input'), true) ?? [];
        $pageId   = (int) ($data['page_id']  ?? 0);
        $type     =        $data['type']      ?? 'text';
        $content  =        $data['content']   ?? '';
        $position = isset($data['position']) ? (int) $data['position'] : null;

        $allowed = ['text', 'heading', 'checklist'];

        if (!$pageId || !in_array($type, $allowed, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid page_id and type are required.']);
            exit;
        }

        $block = BlockModel::create($pageId, $type, $content, $position);

        echo json_encode([
            'success' => true,
            'block'   => $block,
        ]);
        break;

    // ----------------------------------------------------------
    // Update a block's text content
    // ----------------------------------------------------------
    case 'update':
        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $id      = (int) ($data['id']      ?? 0);
        $content =        $data['content'] ?? '';

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Block ID is required.']);
            exit;
        }

        BlockModel::update($id, $content);
        echo json_encode(['success' => true]);
        break;

    // ----------------------------------------------------------
    // Delete a block and reorder remaining blocks
    // ----------------------------------------------------------
    case 'delete':
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int) ($data['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Block ID is required.']);
            exit;
        }

        BlockModel::delete($id);
        echo json_encode(['success' => true]);
        break;

    // ----------------------------------------------------------
    // Reorder blocks: accepts an ordered array of block IDs
    // ----------------------------------------------------------
    case 'reorder':
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $pageId = (int) ($data['page_id'] ?? 0);
        $order  =        $data['order']   ?? [];

        if (!$pageId || empty($order)) {
            http_response_code(400);
            echo json_encode(['error' => 'page_id and order array are required.']);
            exit;
        }

        BlockModel::reorder($pageId, $order);
        echo json_encode(['success' => true]);
        break;

    // ----------------------------------------------------------
    // Add a new item to a checklist block
    // ----------------------------------------------------------
    case 'add_checklist_item':
        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $blockId = (int) ($data['block_id'] ?? 0);
        $content =        $data['content']  ?? '';

        if (!$blockId) {
            http_response_code(400);
            echo json_encode(['error' => 'Block ID is required.']);
            exit;
        }

        $itemId = BlockModel::createChecklistItem($blockId, $content);
        echo json_encode(['success' => true, 'item_id' => $itemId]);
        break;

    // ----------------------------------------------------------
    // Update the text content of a checklist item
    // ----------------------------------------------------------
    case 'update_checklist_item':
        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $itemId  = (int) ($data['item_id'] ?? 0);
        $content =        $data['content'] ?? '';

        if (!$itemId) {
            http_response_code(400);
            echo json_encode(['error' => 'Item ID is required.']);
            exit;
        }

        BlockModel::updateChecklistItem($itemId, $content);
        echo json_encode(['success' => true]);
        break;

    // ----------------------------------------------------------
    // Toggle checked / unchecked state of a checklist item
    // ----------------------------------------------------------
    case 'toggle_item':
        $data      = json_decode(file_get_contents('php://input'), true) ?? [];
        $itemId    = (int)  ($data['item_id']   ?? 0);
        $isChecked = (bool) ($data['is_checked'] ?? false);

        if (!$itemId) {
            http_response_code(400);
            echo json_encode(['error' => 'Item ID is required.']);
            exit;
        }

        BlockModel::toggleChecklistItem($itemId, $isChecked);
        echo json_encode(['success' => true]);
        break;

    // ----------------------------------------------------------
    // Delete a single checklist item
    // ----------------------------------------------------------
    case 'delete_checklist_item':
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $itemId = (int) ($data['item_id'] ?? 0);

        if (!$itemId) {
            http_response_code(400);
            echo json_encode(['error' => 'Item ID is required.']);
            exit;
        }

        BlockModel::deleteChecklistItem($itemId);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action.']);
}
