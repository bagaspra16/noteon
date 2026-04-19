<?php
require_once __DIR__ . '/config/database.php';
try {
    $db = getDB();
    
    // Create sections table
    $db->exec("CREATE TABLE IF NOT EXISTS sections (
        id VARCHAR(50) PRIMARY KEY,
        workspace_id INT NOT NULL,
        name VARCHAR(150) NOT NULL,
        position INT NOT NULL DEFAULT 0,
        FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // Add section_id to pages
    $db->exec("ALTER TABLE pages ADD COLUMN section_id VARCHAR(50) NULL AFTER workspace_id;");
    $db->exec("ALTER TABLE pages ADD CONSTRAINT fk_pages_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL;");
    
    echo "Success!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
