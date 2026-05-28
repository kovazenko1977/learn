<?php
// PHP routing for development server
if (php_sapi_name() === 'cli-server') {
    $url = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . '/public' . $url['path'];
    if (is_file($file)) {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $mimes = [
            'js'   => 'application/javascript',
            'css'  => 'text/css',
            'svg'  => 'image/svg+xml',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'ico'  => 'image/x-icon',
        ];
        if (isset($mimes[$extension])) {
            header("Content-Type: " . $mimes[$extension]);
        }
        readfile($file);
        return true;
    }
}

// Production / General routing
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = rtrim(dirname($scriptName), '/\\');
$path = substr($requestUri, strlen($basePath));
$pathOnly = explode('?', $path)[0];

define('VSPRINT_BASE_PATH', $basePath);

if (str_starts_with($pathOnly, '/api')) {
    require_once __DIR__ . '/public/api.php';
    exit;
}

$filePath = __DIR__ . '/public' . $pathOnly;
if ($pathOnly !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimes = [
        'js'   => 'application/javascript',
        'css'  => 'text/css',
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'ico'  => 'image/x-icon',
    ];
    if (isset($mimes[$extension])) {
        header("Content-Type: " . $mimes[$extension]);
    }
    readfile($filePath);
    exit;
}

header("Content-Type: text/html; charset=UTF-8");
readfile(__DIR__ . '/public/index.html');
