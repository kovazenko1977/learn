<?php
require_once '../includes/Auth.php';
require_once '../includes/Storage.php';

if (!Auth::check()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$storage = new Storage('chat.json');
$action = $_GET['action'] ?? 'read';

if ($action === 'read') {
    $messages = $storage->read();
    echo json_encode(array_slice($messages, -50)); // Return last 50 messages
    exit;
}

if ($action === 'send') {
    $user = Auth::user();
    $text = $_POST['message'] ?? '';
    $image_path = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($_FILES['image']['tmp_name']);

        if (in_array($mimeType, $allowedTypes)) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
            $image_path = 'uploads/' . $filename;
        }
    }

    if (!empty($text) || $image_path) {
        $messages = $storage->read();
        $newMessage = [
            'id' => uniqid(),
            'user_id' => $user['id'],
            'username' => $user['username'],
            'message' => $text,
            'image' => $image_path,
            'timestamp' => time()
        ];
        $messages[] = $newMessage;
        $storage->write($messages);
        echo json_encode(['success' => true, 'message' => $newMessage]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Empty message']);
    }
    exit;
}
