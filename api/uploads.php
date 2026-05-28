<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

header('Content-Type: application/json');

$settingsFile = __DIR__ . '/../data/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
$storage = new Storage($settings);
$auth = new Auth($storage, $settings);

$auth->requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = __DIR__ . '/../data/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Whitelist
    $allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    if (!in_array($ext, $allowedExts)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file extension']);
        exit;
    }

    // MIME Check
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = [
        'image/jpeg', 'image/png', 'application/pdf',
        'text/plain', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    if (!in_array($mime, $allowedMimes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file content']);
        exit;
    }

    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    $target = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        echo json_encode(['url' => 'data/uploads/' . $newName]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Upload failed']);
    }
}
