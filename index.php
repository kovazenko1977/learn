<?php
// Root router for VSPRINT
$request_uri = $_SERVER['REQUEST_URI'];
$path_only = explode('?', $request_uri)[0];

// Handle API
if (strpos($request_uri, '/api') === 0) {
    require_once __DIR__ . '/public/api.php';
    exit;
}

// Serve static files from public/
$file = __DIR__ . '/public' . $path_only;
if ($path_only !== '/' && file_exists($file) && !is_dir($file)) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $mimes = [
        'js'   => 'application/javascript',
        'css'  => 'text/css',
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
    ];

    if (isset($mimes[$ext])) {
        header("Content-Type: " . $mimes[$ext]);
    } else {
        header("Content-Type: " . mime_content_type($file));
    }
    readfile($file);
    exit;
}

// Fallback to index.html for SPA
$index = __DIR__ . '/public/index.html';
if (file_exists($index)) {
    header("Content-Type: text/html");
    readfile($index);
} else {
    echo "<h1>VSPRINT Backend is running</h1><p>Frontend assets not found in /public. Please run 'npm run build' in /frontend.</p>";
}
