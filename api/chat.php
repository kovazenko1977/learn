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
    $recipient = $_GET['recipient'] ?? 'all';
    $search = $_GET['search'] ?? '';

    $messages = array_filter($chat, function($m) use ($currentUser, $recipient, $search) {
        $inThread = false;
        if ($recipient === 'all') {
            $inThread = ($m['recipient'] === 'all');
        } else {
            $inThread = ($m['sender_id'] === $currentUser['id'] && $m['recipient'] === $recipient) ||
                       ($m['sender_id'] === $recipient && $m['recipient'] === $currentUser['id']);
        }

        if (!$inThread) return false;

        if ($search) {
            return mb_stripos($m['text'], $search) !== false;
        }

        return true;
    });

    // Mark messages as read by current user
    $updated = false;
    foreach ($chat as &$m) {
        if ($m['recipient'] === $currentUser['id'] && $m['sender_id'] === $recipient) {
            if (!isset($m['read_by'])) $m['read_by'] = [];
            if (!in_array($currentUser['id'], $m['read_by'])) {
                $m['read_by'][] = $currentUser['id'];
                $updated = true;
            }
        } elseif ($m['recipient'] === 'all' && $m['sender_id'] !== $currentUser['id']) {
            if (!isset($m['read_by'])) $m['read_by'] = [];
            if (!in_array($currentUser['id'], $m['read_by'])) {
                $m['read_by'][] = $currentUser['id'];
                $updated = true;
            }
        }
    }

    if ($updated) {
        Storage::save('chat', $chat);
    }

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
        'reply_to' => $data['reply_to'] ?? null,
        'reply_text' => $data['reply_text'] ?? null,
        'timestamp' => date('Y-m-d H:i:s'),
        'read_by' => []
    ];

    $chat[] = $msg;
    if (count($chat) > 1000) $chat = array_slice($chat, -1000);

    Storage::save('chat', $chat);
    echo json_encode(['success' => true, 'message' => $msg]);
}
