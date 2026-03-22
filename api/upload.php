<?php
require_once '../includes/AuthManager.php';
require_once '../includes/Storage.php';
header('Content-Type: application/json');

$auth = new AuthManager();
$user = $auth->getCurrentUser();
$storage = new Storage();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

if (!isset($_FILES['image']) && !isset($_FILES['file']) && !isset($_FILES['audio'])) {
    echo json_encode(['success' => false, 'message' => 'Файл не найден']);
    exit;
}

$file = isset($_FILES['image']) ? $_FILES['image'] : (isset($_FILES['file']) ? $_FILES['file'] : $_FILES['audio']);
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'webm', 'mp3', 'ogg', 'wav'];

if (!in_array(strtolower($ext), $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Неверный формат файла']);
    exit;
}

$filename = bin2hex(random_bytes(16)) . '.' . $ext;
$target = $storage->getUploadDir() . $filename;

if (move_uploaded_file($file['tmp_name'], $target)) {
    echo json_encode(['success' => true, 'url' => 'uploads/' . $filename]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при загрузке']);
}
