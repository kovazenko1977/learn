<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';

$user = Auth::getUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'zip', 'rar'];

if (!in_array($ext, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type']);
    exit;
}

$dir = __DIR__ . '/../uploads/';
if (!is_dir($dir)) mkdir($dir, 0777, true);

$newName = bin2hex(random_bytes(8)) . '.' . $ext;
$path = $dir . $newName;

if (move_uploaded_file($file['tmp_name'], $path)) {
    echo json_encode(['success' => true, 'path' => 'uploads/' . $newName, 'name' => $file['name']]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to move uploaded file']);
}
