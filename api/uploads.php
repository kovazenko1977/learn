<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$uploadDir = __DIR__ . '/../data/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt', 'zip'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExts)) {
        echo json_encode(['error' => 'Unsupported file type']);
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        echo json_encode(['error' => 'File too large (max 5MB)']);
        exit;
    }

    $fileName = uniqid() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
        echo json_encode([
            'success' => true,
            'file_url' => 'data/uploads/' . $fileName,
            'file_name' => $file['name']
        ]);
    } else {
        echo json_encode(['error' => 'Upload failed']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
    $files = [];
    if (is_dir($uploadDir)) {
        $items = array_diff(scandir($uploadDir), ['.', '..']);
        foreach ($items as $item) {
            $files[] = [
                'name' => $item,
                'size' => filesize($uploadDir . $item),
                'date' => date('Y-m-d H:i:s', filemtime($uploadDir . $item))
            ];
        }
    }
    echo json_encode($files);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['name'])) {
    $name = basename($_GET['name']);
    if (file_exists($uploadDir . $name)) {
        unlink($uploadDir . $name);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'File not found']);
    }
    exit;
}
