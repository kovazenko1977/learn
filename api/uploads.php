<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';

$user = Auth::authenticate();
if (!$user) {
    http_response_code(401);
    exit;
}

if ($_FILES['file']) {
    $file = $_FILES['file'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar'];

    if (!in_array(strtolower($ext), $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Недопустимый тип файла']);
        exit;
    }

    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    $target = __DIR__ . '/../uploads/' . $newName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        echo json_encode([
            'success' => true,
            'name' => $file['name'],
            'url' => 'uploads/' . $newName
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка при загрузке']);
    }
}