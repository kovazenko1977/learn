<?php
session_start();

function getConfig() {
    $configFile = __DIR__ . '/data/config.json';
    if (!file_exists($configFile)) {
        return ['admin_pass_hash' => '$2y$10$7mwV7q.BdFuDu5/opP2wpuc0e4A5h1gycvArWSCif/Ghq9aQca2y2'];
    }
    return json_decode(file_get_contents($configFile), true);
}

function saveConfig($config) {
    $configFile = __DIR__ . '/data/config.json';
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
}

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
