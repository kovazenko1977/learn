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
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = uniqid() . '.' . $ext;
    $target = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        echo json_encode(['url' => 'data/uploads/' . $newName]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Upload failed']);
    }
}
