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

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . str_replace('"', '_', $file['original_name']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
