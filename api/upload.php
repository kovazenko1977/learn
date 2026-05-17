<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;

if (!Auth::check()) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['error' => 'Недопустимый тип файла']);
        exit;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $uploadDir = __DIR__ . '/../data/uploads/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host = $_SERVER['HTTP_HOST'];
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']); // /api
        $baseUrl = str_replace('/api', '', $scriptPath);

        $url = "$protocol://$host$baseUrl/api/image.php?name=" . $filename;
        echo json_encode(['url' => $url]);
    } else {
        echo json_encode(['error' => 'Ошибка загрузки файла']);
    }
}
