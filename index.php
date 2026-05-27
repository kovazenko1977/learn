<?php
/**
 * LabelPro PHP Entry Point
 * This script handles requests when deployed on a standard PHP web server.
 * It proxies API requests to the Python backend and serves frontend assets.
 */

$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if (str_starts_with($request_uri, '/api/') || str_starts_with($request_uri, '/token')) {
    $backend_url = 'http://localhost:8000' . $request_uri;

    $headers = [];
    foreach (getallheaders() as $name => $value) {
        $headers[] = "$name: $value";
    }

    $ch = curl_init($backend_url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    curl_close($ch);

    header("Content-Type: $content_type");
    http_response_code($http_code);
    echo $response;
} else {
    // Serve frontend build
    $file = __DIR__ . '/frontend/dist' . ($request_uri === '/' ? '/index.html' : $request_uri);
    if (file_exists($file) && !is_dir($file)) {
        $mime = mime_content_type($file);
        header("Content-Type: $mime");
        readfile($file);
    } else {
        if (file_exists(__DIR__ . '/frontend/dist/index.html')) {
            include __DIR__ . '/frontend/dist/index.html';
        } else {
            echo "<h1>LabelPro</h1><p>Frontend not built. Please run 'npm run build' in the frontend directory.</p>";
        }
    }
}
