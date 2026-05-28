<?php
/**
 * VSPRINT Unified Router
 * Works on local dev and various PHP hostings (Apache, Nginx, etc.)
 */

// Define the root directory
$rootDir = __DIR__;
$publicDir = $rootDir . '/public';

// Get the requested URI path
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 1. Route API requests
if (strpos($requestUri, '/api') === 0) {
    require_once $publicDir . '/api.php';
    exit;
}

// 2. Serve physical files from public/ if they exist
$filePath = $publicDir . $requestUri;
if ($requestUri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);

    $mimeTypes = [
        'js'   => 'application/javascript',
        'css'  => 'text/css',
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'json' => 'application/json',
    ];

    if (isset($mimeTypes[$extension])) {
        header('Content-Type: ' . $mimeTypes[$extension]);
    } else {
        // Fallback
        if (function_exists('mime_content_type')) {
            header('Content-Type: ' . mime_content_type($filePath));
        }
    }

    readfile($filePath);
    exit;
}

// 3. Fallback to SPA entry point (index.html)
$indexFile = $publicDir . '/index.html';
if (file_exists($indexFile)) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($indexFile);
} else {
    header('HTTP/1.1 404 Not Found');
    echo "<h1>VSPRINT: Frontend not found</h1><p>Please build the project first.</p>";
}
