<?php
$name = $_GET['name'] ?? '';
if (!$name || !preg_match('/^[a-z0-9.]+$/i', $name)) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$filepath = __DIR__ . '/../data/uploads/' . $name;
if (file_exists($filepath)) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    header('Content-Type: ' . finfo_file($finfo, $filepath));
    readfile($filepath);
} else {
    header('HTTP/1.1 404 Not Found');
}
