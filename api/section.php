<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$db = getDB();

try {
    switch ($action) {
        case 'list':
            $workspaceId = $_GET['workspace_id'] ?? null;
            if (!$workspaceId) throw new Exception('workspace_id required');
            $stmt = $db->prepare('SELECT * FROM sections WHERE workspace_id = ? ORDER BY position ASC, id ASC');
            $stmt->execute([$workspaceId]);
            echo json_encode(['success' => true, 'sections' => $stmt->fetchAll()]);
            break;

        case 'create':
            $input = json_decode(file_get_contents('php://input'), true);
            $workspaceId = $input['workspace_id'] ?? null;
            $name = $input['name'] ?? 'New Section';
            $icon = $input['icon'] ?? '📁';
            $id = $input['id'] ?? 'grp-' . round(microtime(true) * 1000);
            
            if (!$workspaceId) throw new Exception('workspace_id required');
            
            $stmt = $db->prepare('INSERT INTO sections (id, workspace_id, name, icon) VALUES (?, ?, ?, ?)');
            $stmt->execute([$id, $workspaceId, $name, $icon]);
            
            echo json_encode(['success' => true, 'id' => $id, 'name' => $name, 'icon' => $icon]);
            break;

        case 'rename':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            $name = $input['name'] ?? null;
            $icon = $input['icon'] ?? '📁';
            
            if (!$id || !$name) throw new Exception('id and name required');
            
            $stmt = $db->prepare('UPDATE sections SET name = ?, icon = ? WHERE id = ?');
            $stmt->execute([$name, $icon, $id]);
            
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            
            if (!$id) throw new Exception('id required');
            
            $stmt = $db->prepare('DELETE FROM sections WHERE id = ?');
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
