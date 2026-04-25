<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];

    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Extension not allowed']);
        exit;
    }

    $name = uniqid() . '.' . $ext;
    $target = __DIR__ . '/../uploads/' . $name;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        echo json_encode(['success' => true, 'filename' => $name, 'original_name' => $file['name']]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to move file']);
    }
    exit;
}
