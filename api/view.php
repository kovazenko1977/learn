<?php
require_once __DIR__ . '/../includes/Storage.php';
use App\Storage;

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    die("ID is required");
}

$file = Storage::getById($id);

if (!$file) {
    http_response_code(404);
    die("File not found");
}

$filePath = __DIR__ . '/../uploads/' . $file['filename'];

if (!file_exists($filePath)) {
    http_response_code(404);
    die("File not found on disk");
}

$mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . str_replace('"', '_', $file['original_name']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: public, max-age=3600');

readfile($filePath);
exit;
