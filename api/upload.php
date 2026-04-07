<?php
require_once __DIR__ . '/../includes/Security.php';

Security::checkAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error = $_FILES['file']['error'] ?? 'No file uploaded';
        $msg = 'Ошибка загрузки: ';
        switch($error) {
            case UPLOAD_ERR_INI_SIZE: $msg .= 'Файл слишком большой (превышен предел сервера)'; break;
            case UPLOAD_ERR_FORM_SIZE: $msg .= 'Файл слишком большой (превышен предел формы)'; break;
            case UPLOAD_ERR_PARTIAL: $msg .= 'Файл загружен частично'; break;
            case UPLOAD_ERR_NO_FILE: $msg .= 'Файл не выбран'; break;
            default: $msg .= 'Код ошибки ' . $error;
        }
        echo json_encode(['error' => $msg]);
        exit;
    }

    $file = $_FILES['file'];
    $description = $_POST['description'] ?? '';

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'zip', 'rar', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedExtensions)) {
        echo json_encode(['error' => 'Unsupported file type']);
        exit;
    }

    $targetDir = __DIR__ . '/../data/uploads/';
    $fileName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
    $targetPath = $targetDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $filesDataFile = __DIR__ . '/../data/files.json';
        $files = json_decode(file_get_contents($filesDataFile), true) ?: [];

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $baseDir = str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__ . '/..'));
        $publicPath = $protocol . $host . $baseDir . '/data/uploads/' . $fileName;

        $newFile = [
            'id' => uniqid(),
            'name' => basename($file['name']),
            'description' => $description,
            'fileName' => $fileName,
            'size' => $file['size'],
            'type' => $file['type'],
            'uploadDate' => date('Y-m-d H:i:s'),
            'fullPath' => $publicPath
        ];

        $files[] = $newFile;
        file_put_contents($filesDataFile, json_encode($files, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

        Security::log("File uploaded: " . $newFile['name']);
        echo json_encode(['success' => true, 'file' => $newFile]);
    } else {
        echo json_encode(['error' => 'Failed to move uploaded file']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
