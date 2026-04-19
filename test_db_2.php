<?php
require_once __DIR__ . '/config/database.php';
try {
    $db = getDB();
    
    // Add icon to sections
    $db->exec("ALTER TABLE sections ADD COLUMN icon VARCHAR(50) DEFAULT '📁' AFTER workspace_id;");
    
    echo "Success!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
