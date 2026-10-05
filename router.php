<?php
// router.php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (preg_match('#^/api/#', $uri)) {
    require __DIR__ . '/api/router.php';
    exit;
}

if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

require __DIR__ . '/index.html';
