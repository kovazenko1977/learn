<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$storage = Storage::getInstance();
$currentUser = Auth::requireUser();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'my-settings') {
    echo json_encode([
        'success' => true,
        'notifications' => [
            'notif_email' => $currentUser['notif_email'] ?? true,
            'notif_push' => $currentUser['notif_push'] ?? true,
            'notif_telegram' => $currentUser['notif_telegram'] ?? false,
            'telegram_chat_id' => $currentUser['telegram_chat_id'] ?? ''
        ]
    ]);
    exit;
}

if ($method === 'POST' && $action === 'save') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $storage->update('users', $currentUser['id'], [
        'notif_email' => (bool)($input['notif_email'] ?? true),
        'notif_push' => (bool)($input['notif_push'] ?? true),
        'notif_telegram' => (bool)($input['notif_telegram'] ?? false),
        'telegram_chat_id' => trim($input['telegram_chat_id'] ?? '')
    ]);

    echo json_encode(['success' => true, 'message' => 'Настройки уведомлений обновлены']);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Неизвестный запрос']);
