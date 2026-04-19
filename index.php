<?php
/**
 * index.php — Root entry point
 *
 * Redirects authenticated users to the editor,
 * all others to the landing / auth page.
 */

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /noteon/views/editor.php');
} else {
    header('Location: /noteon/views/index.php');
}

exit;
