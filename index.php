<?php
// Root router for dev/prod
$request_uri = $_SERVER['REQUEST_URI'];

// Serve static files from public/
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|svg|ico|woff|woff2)$/', $request_uri)) {
    $file = __DIR__ . '/public' . explode('?', $request_uri)[0];
    if (file_exists($file)) {
        if (str_ends_with($file, '.js')) {
            header("Content-Type: application/javascript");
        } elseif (str_ends_with($file, '.css')) {
            header("Content-Type: text/css");
        } else {
            $mime = mime_content_type($file);
            header("Content-Type: $mime");
        }
        readfile($file);
        exit;
    }
}

// Route API requests
if (strpos($request_uri, '/api') === 0) {
    require_once __DIR__ . '/public/api.php';
    exit;
}

// Default to frontend index
$index = __DIR__ . '/public/index.html';
if (file_exists($index)) {
    readfile($index);
} else {
    echo "VSPRINT Backend is running. Frontend not built.";
}
