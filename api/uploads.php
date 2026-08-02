<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/TokenProvider.php';

// Strictly verify JWT token for all uploads
$currentUser = TokenProvider::getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['file'])) {
    $file = $_FILES['file'];
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip', 'docx', 'xlsx'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File type not allowed']);
        exit;
    }

    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $path = __DIR__ . '/../uploads/' . $name;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        echo json_encode(['success' => true, 'url' => 'uploads/' . $name, 'name' => $file['name']]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Upload failed']);
    }
}
