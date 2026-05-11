<?php
require_once __DIR__ . '/../includes/Storage.php';
use App\Storage;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
$fileName = basename($file['name']);
$fileTmpPath = $file['tmp_name'];
$fileSize = $file['size'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

$blacklistedExtensions = ['php', 'phtml', 'php5', 'php7', 'js', 'html', 'htm', 'exe', 'sh', 'bat', 'cgi', 'pl', 'py'];

if (in_array($fileExtension, $blacklistedExtensions)) {
    http_response_code(400);
    echo json_encode(['error' => 'File type not allowed for security reasons']);
    exit;
}

$uniqueName = uniqid() . '_' . $fileName;
$destPath = __DIR__ . '/../uploads/' . $uniqueName;

if (move_uploaded_file($fileTmpPath, $destPath)) {
    $fileData = [
        'original_name' => $fileName,
        'filename' => $uniqueName,
        'size' => $fileSize,
        'extension' => $fileExtension
    ];
    $savedFile = Storage::save($fileData);
    echo json_encode($savedFile);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to move uploaded file']);
}
