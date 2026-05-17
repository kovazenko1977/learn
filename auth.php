<?php
session_start();

// In a real production app, use password_hash() and a database.
// This is a boilerplate configuration.
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', '$2y$10$vO8qK/.R1/RMB9D8Y7uGuekQWpM9OQoV9U8D9U8D9U8D9U8D9U8D9'); // hash of 'admin123'

function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: login.php');
        exit;
    }
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function secureUnlink($path) {
    $realUploadsDir = realpath(__DIR__ . '/uploads');
    $realPath = realpath(__DIR__ . $path);

    if ($realPath && strpos($realPath, $realUploadsDir) === 0 && is_file($realPath)) {
        unlink($realPath);
    }
}
