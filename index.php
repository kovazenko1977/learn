<?php
/**
 * LabelPro Elite - Root Router
 * This script allows running the application from the root directory
 * using the built-in PHP development server or as a CGI entry point.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// 1. Handle API calls
if (strpos($uri, '/api.php') === 0) {
    require_once __DIR__ . '/public/api.php';
    exit;
}

// 2. Serve static files from /public
$publicFile = __DIR__ . '/public' . $uri;
if ($uri !== '/' && file_exists($publicFile) && !is_dir($publicFile)) {
    // Determine MIME type
    $ext = pathinfo($publicFile, PATHINFO_EXTENSION);
    $mimes = [
        'css' => 'text/css',
        'js'  => 'application/javascript',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'ico' => 'image/x-icon',
        'json'=> 'application/json',
        'pdf' => 'application/pdf'
    ];

    if (isset($mimes[$ext])) {
        header("Content-Type: " . $mimes[$ext]);
    } else {
        header("Content-Type: " . (mime_content_type($publicFile) ?: 'application/octet-stream'));
    }

    readfile($publicFile);
    exit;
}

// 3. Fallback to SPA index.html for all other routes
header("Content-Type: text/html");
readfile(__DIR__ . '/public/index.html');
exit;
