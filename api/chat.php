<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/TokenProvider.php';
require_once __DIR__ . '/../includes/Permissions.php';

$storage = new Storage();
$currentUser = TokenProvider::getCurrentUser();

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check banned
$currentUserFull = $storage->getById('users', $currentUser['id']);
if ($currentUserFull && !empty($currentUserFull['banned'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'User is banned']);
    exit;
}

// Check chat permission
if (!Permissions::check($currentUserFull, 'can_access_chat', $storage)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'У вас нет доступа к чату']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $recipient_id = $_GET['recipient_id'] ?? null; // empty or 'general' means General Chat
    $messages = $storage->getAll('chat');

    // Filter messages
    $filtered = [];
    foreach ($messages as $msg) {
        $msg_recipient = $msg['recipient_id'] ?? null;
        if (empty($recipient_id) || $recipient_id === 'general' || $recipient_id === 'null') {
            // General Chat: recipient_id is empty/null
            if (empty($msg_recipient) || $msg_recipient === 'null') {
                $filtered[] = $msg;
            }
        } else {
            // Private Chat: between currentUser and recipient_id
            if (
                ($msg['sender_id'] === $currentUser['id'] && $msg_recipient === $recipient_id) ||
                ($msg['sender_id'] === $recipient_id && $msg_recipient === $currentUser['id'])
            ) {
                $filtered[] = $msg;
            }
        }
    }

    // Sort messages chronologically by created_at or sequence
    usort($filtered, function($a, $b) {
        return strcmp($a['created_at'] ?? '', $b['created_at'] ?? '');
    });

    echo json_encode(array_values($filtered));
    exit;
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid message body']);
        exit;
    }

    $recipient_id = $data['recipient_id'] ?? null;
    if ($recipient_id === 'general' || $recipient_id === 'null' || empty($recipient_id)) {
        $recipient_id = null;
    }

    $newMessage = [
        'sender_id' => $currentUser['id'],
        'recipient_id' => $recipient_id,
        'message' => $data['message'] ?? '',
        'attachment_url' => $data['attachment_url'] ?? null,
        'attachment_name' => $data['attachment_name'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $msgId = $storage->save('chat', $newMessage);
    echo json_encode(['success' => true, 'id' => $msgId]);
    exit;
}
