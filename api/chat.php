<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';

Auth::requireAuth();
$currentUser = Auth::getUser();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $chat = Storage::read('chat');
    $recipient = $_GET['recipient'] ?? 'all'; // 'all' for general chat

    $messages = array_filter($chat, function($m) use ($currentUser, $recipient) {
        if ($recipient === 'all') return $m['recipient'] === 'all';
        return ($m['sender_id'] === $currentUser['id'] && $m['recipient'] === $recipient) ||
               ($m['sender_id'] === $recipient && $m['recipient'] === $currentUser['id']);
    });

    echo json_encode(array_values($messages));
} elseif ($method === 'POST') {
    $data = Security::sanitize(json_decode(file_get_contents('php://input'), true));
    $chat = Storage::read('chat');

    $msg = [
        'id' => uniqid('msg_'),
        'sender_id' => $currentUser['id'],
        'sender_name' => $currentUser['name'],
        'recipient' => $data['recipient'] ?? 'all',
        'text' => $data['text'] ?? '',
        'timestamp' => date('Y-m-d H:i:s')
    ];

    $chat[] = $msg;
    // Keep last 500 messages
    if (count($chat) > 500) $chat = array_slice($chat, -500);

    Storage::save('chat', $chat);
    echo json_encode(['success' => true, 'message' => $msg]);
}
