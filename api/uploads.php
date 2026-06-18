<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$settingsFile = __DIR__ . '/../data/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];

$storage = new Storage($settings);
$auth = new Auth($storage, $settings);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Files can be uploaded publicly (for new requests) or by authenticated users
    // Authenticated users have fewer restrictions if needed, but for now we keep it simple

    if (!isset($_FILES['file'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['file'];
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip', 'docx', 'xlsx'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $target = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        echo json_encode(['url' => 'uploads/' . $filename]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Failed to move uploaded file']);
    }
}
