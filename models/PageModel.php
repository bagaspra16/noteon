<?php
/**
 * models/PageModel.php
 *
 * Handles all page operations.
 * Pages are hierarchical via parent_id (self-referencing FK).
 * Deleting a page cascades to all child pages and their blocks.
 */

require_once __DIR__ . '/../config/database.php';

class PageModel
{
    /**
     * Create a new page inside a workspace.
     *
     * @param int      $workspaceId  The parent workspace.
     * @param string   $title        Page title.
     * @param int|null $parentId     Optional parent page (for nesting).
     */
    public static function create(int $workspaceId, string $title, ?int $parentId = null, ?string $sectionId = null): array
    {
        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO pages (workspace_id, section_id, parent_id, title) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$workspaceId, $sectionId, $parentId, $title]);

        return self::findById((int) $db->lastInsertId());
    }

    /**
     * Fetch all pages in a workspace ordered by creation time.
     * The frontend is responsible for building the tree from parent_id.
     */
    public static function listByWorkspace(int $workspaceId): array
    {
        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT * FROM pages WHERE workspace_id = ? ORDER BY created_at ASC'
        );
        $stmt->execute([$workspaceId]);

        return $stmt->fetchAll();
    }

    /**
     * Find a single page by its primary key.
     */
    public static function findById(int $id): ?array
    {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM pages WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row  = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Load a page together with all its blocks (and checklist items).
     * Blocks are ordered by position ascending.
     */
    public static function getWithBlocks(int $id): ?array
    {
        $db = getDB();

        // Load the page
        $stmt = $db->prepare('SELECT * FROM pages WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $page = $stmt->fetch();

        if (!$page) {
            return null;
        }

        // Load all blocks for this page
        $stmt = $db->prepare(
            'SELECT * FROM blocks WHERE page_id = ? ORDER BY position ASC'
        );
        $stmt->execute([$id]);
        $blocks = $stmt->fetchAll();

        // Enrich checklist blocks with their items
        foreach ($blocks as &$block) {
            if ($block['type'] === 'checklist') {
                $stmt = $db->prepare(
                    'SELECT * FROM checklist_items WHERE block_id = ? ORDER BY id ASC'
                );
                $stmt->execute([$block['id']]);
                $block['items'] = $stmt->fetchAll();
            } else {
                $block['items'] = [];
            }
        }
        unset($block); // break the reference

        $page['blocks'] = $blocks;

        return $page;
    }

    /**
     * Update a page's title and set updated_at to now.
     */
    public static function update(int $id, string $title): bool
    {
        $db   = getDB();
        $stmt = $db->prepare(
            'UPDATE pages SET title = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$title, $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete a page by ID.
     * Child pages, blocks, and checklist_items are removed automatically
     * by the ON DELETE CASCADE foreign keys in the schema.
     */
    public static function delete(int $id): bool
    {
        $db   = getDB();
        $stmt = $db->prepare('DELETE FROM pages WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }
}
