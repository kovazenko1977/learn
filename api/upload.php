<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;

if (!Auth::check()) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$uploadDir = __DIR__ . '/../data/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Support both 'image', 'video', 'media', and 'file' input keys
$file = $_FILES['file'] ?? $_FILES['media'] ?? $_FILES['video'] ?? $_FILES['image'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $file) {
    header('Content-Type: application/json');

    $allowedMimes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'
    ];
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm', 'ogv', 'ogg', 'mov'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedMimes) && !in_array($ext, $allowedExts)) {
        echo json_encode(['error' => 'Недопустимый формат файла (разрешены фото и видео: JPG, PNG, GIF, WEBP, MP4, WEBM)']);
        exit;
    }

    $isVideo = str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'webm', 'ogv', 'ogg', 'mov']);
    $prefix = $isVideo ? 'vid_' : 'img_';
    $filename = uniqid($prefix) . '.' . $ext;

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host = $_SERVER['HTTP_HOST'];

        $currentDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $parentDir = str_replace('\\', '/', dirname($currentDir));
        $baseUrl = ($parentDir === '/' || $parentDir === '.') ? '' : $parentDir;

        $url = "$protocol://$host$baseUrl/api/image.php?name=" . $filename;
        $relUrl = "api/image.php?name=" . $filename;

        Auth::log("Загружен медиафайл: $filename (" . ($isVideo ? 'видео' : 'изображение') . ")");

        echo json_encode([
            'success' => true,
            'url' => $url,
            'relative_url' => $relUrl,
            'filename' => $filename,
            'type' => $isVideo ? 'video' : 'image',
            'mime' => $mime
        ]);
    } else {
        echo json_encode(['error' => 'Ошибка сохранения файла на сервере']);
    }
    exit;
}

echo json_encode(['error' => 'Файл не передан']);
