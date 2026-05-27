<?php
/**
 * LabelPro - Root Entry Point
 */

define('BACKEND_URL', 'http://localhost:8000');

$request = $_SERVER['REQUEST_URI'];
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
        'html' => 'text/html',
        'json' => 'application/json'
    ];
    $mime = isset($mimes[$ext]) ? $mimes[$ext] : 'application/octet-stream';
    header("Content-Type: $mime");
    readfile($publicFile);
    exit;
}

// Fallback to index.html for SPA routing or root
header("Content-Type: text/html");
readfile(__DIR__ . '/public/index.html');
