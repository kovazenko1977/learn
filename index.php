<?php
/**
 * LabelPro - Root Entry Point
 * This script serves the frontend and checks if the backend is available.
 */

define('BACKEND_URL', 'http://localhost:8000');

// Simple router
$request = $_SERVER['REQUEST_URI'];
$basePath = dirname($_SERVER['PHP_SELF']);
if ($basePath != '/') {
    $request = str_replace($basePath, '', $request);
}

// Serve public files if they exist
$publicFile = __DIR__ . '/public' . $request;
if ($request != '/' && file_exists($publicFile) && !is_dir($publicFile)) {
    $mime = mime_content_type($publicFile);
    header("Content-Type: $mime");
    readfile($publicFile);
    exit;
}

// Default to serving the index
if (file_exists(__DIR__ . '/public/index.html')) {
    readfile(__DIR__ . '/public/index.html');
} else {
    echo "<h1>LabelPro</h1>";
    echo "<p>Frontend not built. Please run <code>npm run build</code> in the frontend directory.</p>";
}
