<?php
$uri = decode_uri(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

function decode_uri($uri) {
    return rawurldecode($uri);
}

if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

if (str_starts_with($uri, '/api')) {
    require_once __DIR__ . '/api/index.php';
    exit;
}

if (file_exists(__DIR__ . '/index.php')) {
    require_once __DIR__ . '/index.php';
    exit;
}

require_once __DIR__ . '/index.html';
