<?php
require_once __DIR__ . '/../includes/auth.php';
require_auth();

header('Content-Type: application/json');

$uploadDir = __DIR__ . '/../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Allowed extensions
$allowedExtensions = ['pdf', 'docx', 'txt', 'jpg', 'png', 'zip', 'csv'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $fileName = basename($file['name']);
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden file type']);
        exit;
    }

    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode(['status' => 'success', 'fileName' => $fileName]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Upload failed']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $files = array_diff(scandir($uploadDir), array('.', '..'));
    echo json_encode(array_values($files));
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $fileName = basename($_GET['name'] ?? ''); // basename for security
    if ($fileName && file_exists($uploadDir . $fileName)) {
        unlink($uploadDir . $fileName);
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'File not found']);
    }
}
