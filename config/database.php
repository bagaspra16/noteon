<?php
/**
 * config/database.php
 *
 * Returns a shared PDO instance (lazy singleton).
 * Supports only ONE database — hagglenote.
 *
 * Usage: $pdo = getDB();
 */

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host     = '127.0.0.1';
        $port     = 3306;
        $dbname   = 'hagglenote';
        $username = 'root';
        $password = '';
        $charset  = 'utf8mb4';

        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, $username, $password, $options);
    }

    return $pdo;
}
