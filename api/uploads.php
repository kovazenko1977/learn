<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/TokenProvider.php';

$tokenProvider = new TokenProvider();
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);
$userData = $tokenProvider->validateToken($token);

if (!$userData) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File type not allowed']);
        exit;
    }

    $newName = bin2hex(random_bytes(8)) . '.' . $ext;
    $dir = __DIR__ . '/../uploads/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $target = $dir . $newName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        echo json_encode(['success' => true, 'filename' => $newName, 'original_name' => $file['name']]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
    }
    exit;
}
