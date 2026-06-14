<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Security: Whitelist allowed extensions
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip', 'docx', 'xlsx'];
    if (!in_array($ext, $allowed)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Тип файла не разрешен']);
        exit;
    }

    // Security: Prevent execution of uploaded files (e.g. .php, .phtml, etc.)
    // already handled by whitelist, but good to be explicit or use safe filename
    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        echo json_encode(['url' => 'uploads/' . $filename]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Ошибка загрузки файла']);
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Файл не найден']);
}
