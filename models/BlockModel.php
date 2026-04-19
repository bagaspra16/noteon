<?php
/**
 * models/BlockModel.php
 *
 * Handles block CRUD and position management within a page.
 * Also manages checklist_items for checklist-type blocks.
 *
 * Position logic:
 *   - Positions are 1-based integers.
 *   - Inserting at end: MAX(position) + 1.
 *   - Inserting at position N: shift all blocks at N or above up by 1 first.
 *   - Deleting at position N: shift all blocks above N down by 1 after.
 */

require_once __DIR__ . '/../config/database.php';

class BlockModel
{
    // ----------------------------------------------------------------
    // Block operations
    // ----------------------------------------------------------------

    /**
     * Create a new block in a page.
     *
     * @param int         $pageId    The target page.
     * @param string      $type      'text' | 'heading' | 'checklist'
     * @param string      $content   Initial text content.
     * @param int|null    $position  Insert at position (null = append to end).
     */
    public static function create(int $pageId, string $type, string $content = '', ?int $position = null): array
    {
        $db = getDB();

        if ($position === null) {
            // Append: position = max + 1
            $stmt = $db->prepare(
                'SELECT COALESCE(MAX(position), 0) + 1 FROM blocks WHERE page_id = ?'
            );
            $stmt->execute([$pageId]);
            $position = (int) $stmt->fetchColumn();
        } else {
            // Shift existing blocks at and after the target position up by 1
            $stmt = $db->prepare(
                'UPDATE blocks SET position = position + 1 WHERE page_id = ? AND position >= ?'
            );
            $stmt->execute([$pageId, $position]);
        }

        $stmt = $db->prepare(
            'INSERT INTO blocks (page_id, type, content, position) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$pageId, $type, $content, $position]);

        return self::findById((int) $db->lastInsertId());
    }

    /**
     * Find a block by its primary key.
     */
    public static function findById(int $id): ?array
    {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM blocks WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row  = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Update a block's text content.
     */
    public static function update(int $id, string $content): bool
    {
        $db   = getDB();
        $stmt = $db->prepare('UPDATE blocks SET content = ? WHERE id = ?');
        $stmt->execute([$content, $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete a block and shift subsequent blocks' positions down by 1.
     */
    public static function delete(int $id): bool
    {
        $db    = getDB();
        $block = self::findById($id);

        if (!$block) {
            return false;
        }

        $stmt = $db->prepare('DELETE FROM blocks WHERE id = ?');
        $stmt->execute([$id]);

        // Close the gap left by the removed block
        $stmt = $db->prepare(
            'UPDATE blocks SET position = position - 1 WHERE page_id = ? AND position > ?'
        );
        $stmt->execute([$block['page_id'], $block['position']]);

        return true;
    }

    /**
     * Reorder blocks in a page based on an ordered array of block IDs.
     *
     * @param int   $pageId  The page the blocks belong to.
     * @param array $order   Array of block IDs in the desired order (index 0 = position 1).
     */
    public static function reorder(int $pageId, array $order): bool
    {
        $db = getDB();

        foreach ($order as $index => $blockId) {
            $stmt = $db->prepare(
                'UPDATE blocks SET position = ? WHERE id = ? AND page_id = ?'
            );
            $stmt->execute([$index + 1, (int) $blockId, $pageId]);
        }

        return true;
    }

    // ----------------------------------------------------------------
    // Checklist item operations
    // ----------------------------------------------------------------

    /**
     * Add a new item to a checklist block.
     * Returns the new item's ID.
     */
    public static function createChecklistItem(int $blockId, string $content = ''): int
    {
        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO checklist_items (block_id, content, is_checked) VALUES (?, ?, FALSE)'
        );
        $stmt->execute([$blockId, $content]);

        return (int) $db->lastInsertId();
    }

    /**
     * Update the text content of a checklist item.
     */
    public static function updateChecklistItem(int $itemId, string $content): bool
    {
        $db   = getDB();
        $stmt = $db->prepare('UPDATE checklist_items SET content = ? WHERE id = ?');
        $stmt->execute([$content, $itemId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Toggle the checked state of a checklist item.
     *
     * @param bool $isChecked  true = checked, false = unchecked.
     */
    public static function toggleChecklistItem(int $itemId, bool $isChecked): bool
    {
        $db   = getDB();
        $stmt = $db->prepare(
            'UPDATE checklist_items SET is_checked = ? WHERE id = ?'
        );
        $stmt->execute([$isChecked ? 1 : 0, $itemId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete a single checklist item.
     */
    public static function deleteChecklistItem(int $itemId): bool
    {
        $db   = getDB();
        $stmt = $db->prepare('DELETE FROM checklist_items WHERE id = ?');
        $stmt->execute([$itemId]);

        return $stmt->rowCount() > 0;
    }
}
