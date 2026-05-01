<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

header('Content-Type: application/json');

$user = Auth::checkRole(['admin', 'head', 'executor', 'employee']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExtensions)) {
        header('HTTP/1.0 400 Bad Request');
        echo json_encode(['error' => 'Недопустимый формат файла']);
        exit;
    }

    $filename = uniqid() . '.' . $ext;
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode([
            'filename' => $filename,
            'original_name' => $file['name'],
            'url' => '/uploads/' . $filename
        ]);
    } else {
        header('HTTP/1.0 500 Internal Server Error');
        echo json_encode(['error' => 'Ошибка при загрузке файла']);
    }
} else {
    header('HTTP/1.0 400 Bad Request');
    echo json_encode(['error' => 'Файл не получен']);
}
