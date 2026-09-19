<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$storage = Storage::getInstance();
$currentUser = Auth::requireUser();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $ticketId = $_GET['ticket_id'] ?? null;
    if (!$ticketId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Укажите ID заявки']);
        exit;
    }

    $comments = $storage->get('ticket_comments', ['ticket_id' => (int)$ticketId]);
    usort($comments, fn($a, $b) => strcmp($a['created_at'] ?? '', $b['created_at'] ?? ''));

    echo json_encode(['success' => true, 'comments' => $comments]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $ticketId = (int)($input['ticket_id'] ?? 0);
    $commentText = trim($input['comment'] ?? '');
    $attachments = is_array($input['attachments'] ?? null) ? $input['attachments'] : [];
    $isCompletionReport = (bool)($input['is_completion_report'] ?? false);

    if (!$ticketId || (!$commentText && empty($attachments))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Напишите текст комментария или прикрепите файл']);
        exit;
    }

    $ticket = $storage->getById('tickets', $ticketId);
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Заявка не найдена']);
        exit;
    }

    $newComment = [
        'ticket_id' => $ticketId,
        'user_id' => $currentUser['id'],
        'user_name' => $currentUser['full_name'],
        'role' => $currentUser['role'],
        'comment' => $commentText,
        'attachments' => $attachments,
        'is_completion_report' => $isCompletionReport,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $createdComment = $storage->insert('ticket_comments', $newComment);

    // If marked as completion report, automatically change ticket status to completed
    if ($isCompletionReport) {
        $storage->update('tickets', $ticketId, [
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        $storage->update('tickets', $ticketId, [
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Комментарий добавлен',
        'comment' => $createdComment
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
