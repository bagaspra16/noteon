<?php
/**
 * models/UserModel.php
 *
 * Handles all user-related database operations.
 * No HTML or view logic lives here — only data access.
 */

require_once __DIR__ . '/../config/database.php';

class UserModel
{
    /**
     * Register a new user.
     * Returns the new user's ID on success.
     */
    public static function register(string $name, string $email, string $password): int
    {
        $db   = getDB();
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare(
            'INSERT INTO users (name, email, password) VALUES (?, ?, ?)'
        );
        $stmt->execute([$name, $email, $hash]);

        return (int) $db->lastInsertId();
    }

    /**
     * Find a user by email address.
     * Returns the user row or null if not found.
     */
    public static function findByEmail(string $email): ?array
    {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Validate credentials and return the user row.
     * Returns null if email not found or password incorrect.
     */
    public static function login(string $email, string $password): ?array
    {
        $user = self::findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return null;
    }

    /**
     * Find a user by their primary key.
     */
    public static function findById(int $id): ?array
    {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id, name, email, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }
}
