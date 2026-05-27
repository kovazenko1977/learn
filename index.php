<?php
/**
 * LabelPro - Root Entry Point
 */

define('BACKEND_URL', 'http://localhost:8000');

$request = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = str_replace('/index.php', '', $scriptName);

// Clean request from base path
if ($basePath != '' && strpos($request, $basePath) === 0) {
    $request = substr($request, strlen($basePath));
}

// Split URL and Query String
$parts = explode('?', $request);
$cleanRequest = $parts[0];

// Serve public files if they exist
$publicFile = __DIR__ . '/public' . $cleanRequest;
if ($cleanRequest != '/' && $cleanRequest != '' && file_exists($publicFile) && !is_dir($publicFile)) {
    $ext = pathinfo($publicFile, PATHINFO_EXTENSION);
    $mimes = [
        'js' => 'application/javascript',
        'css' => 'text/css',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'html' => 'text/html'
    ];
    $mime = isset($mimes[$ext]) ? $mimes[$ext] : 'application/octet-stream';
    header("Content-Type: $mime");
    readfile($publicFile);
    exit;
}

// Handle API proxying through index.php if needed (optional, but api.php is better)

// Default to serving the index
if (file_exists(__DIR__ . '/public/index.html')) {
    readfile(__DIR__ . '/public/index.html');
} else {
    echo "<h1>LabelPro</h1>";
    echo "<p>Frontend not found. Base path: $basePath, Request: $request</p>";
}
