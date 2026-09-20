<?php
// Router file for PHP built-in web server
$uri = $_SERVER['REQUEST_URI'];
$filePath = __DIR__ . parse_url($uri, PHP_URL_PATH);

if (preg_match('#^/api/#', $uri)) {
    require __DIR__ . '/api/index.php';
    exit;
}

if ($filePath !== __DIR__ && file_exists($filePath) && !is_dir($filePath)) {
    return false;
}

require __DIR__ . '/index.php';
