<?php
// router.php - Entry point for built-in PHP CLI development server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false; // serve static file directly
}

if (str_starts_with($uri, '/api/')) {
    require_once __DIR__ . '/api/router.php';
    exit;
}

require_once __DIR__ . '/index.html';
