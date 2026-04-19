<?php
/**
 * models/WorkspaceModel.php
 *
 * Handles workspace CRUD operations.
 * Each workspace belongs to one user.
 */

require_once __DIR__ . '/../config/database.php';

class WorkspaceModel
{
    /**
     * Create a new workspace for the given user.
     * Returns the full workspace row.
     */
    public static function create(int $userId, string $name): array
    {
        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO workspaces (user_id, name) VALUES (?, ?)'
        );
        $success = $stmt->execute([$userId, $name]);

        if (!$success) {
            return [];
        }

        $id = (int) $db->lastInsertId();
        if ($id === 0) {
            return [];
        }

        $ws = self::findById($id);
        return $ws ?: [];
    }

    /**
     * Fetch all workspaces belonging to a user, newest first.
     */
    public static function listByUser(int $userId): array
    {
        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT * FROM workspaces WHERE user_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    /**
     * Find a workspace by its ID.
     */
    public static function findById(int $id): ?array
    {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM workspaces WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row  = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Delete a workspace — only if it belongs to the given user.
     * Cascades to pages, blocks, and checklist_items via FK.
     * Returns true if a row was actually deleted.
     */
    public static function delete(int $id, int $userId): bool
    {
        $db   = getDB();
        $stmt = $db->prepare(
            'DELETE FROM workspaces WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$id, $userId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Rename a workspace — only if it belongs to the given user.
     * Returns true if a row was updated.
     */
    public static function rename(int $id, int $userId, string $name): bool
    {
        $db   = getDB();
        $stmt = $db->prepare(
            'UPDATE workspaces SET name = ? WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$name, $id, $userId]);

        return $stmt->rowCount() > 0;
    }
}
