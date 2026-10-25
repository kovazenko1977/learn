<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$storage = Storage::getInstance();
$currentUser = Auth::requireUser();

// Only Manager or Admin can assign requests
if ($currentUser['role'] !== 'admin' && $currentUser['role'] !== 'manager') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Назначать исполнителей может только начальник отдела или администратор']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Только POST запросы поддерживаются']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $_GET['action'] ?? 'assign';

if ($action === 'assign' || $action === 'drag-assign') {
    $ticketId = $input['ticket_id'] ?? null;
    $executorId = $input['executor_id'] ?? null;

    if (!$ticketId || !$executorId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Укажите ID заявки и ID исполнителя']);
        exit;
    }

    $ticket = $storage->getById('tickets', $ticketId);
    $executor = $storage->getById('users', $executorId);

    if (!$ticket || !$executor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Заявка или исполнитель не найдены']);
        exit;
    }

    $storage->update('tickets', $ticketId, [
        'assigned_to' => (int)$executorId,
        'status' => 'assigned',
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    // Record comment audit
    $storage->insert('ticket_comments', [
        'ticket_id' => (int)$ticketId,
        'user_id' => $currentUser['id'],
        'user_name' => $currentUser['full_name'],
        'role' => $currentUser['role'],
        'comment' => "Назначен исполнитель: " . $executor['full_name'] . " (" . ($executor['position'] ?? 'Исполнитель') . ")",
        'attachments' => [],
        'is_completion_report' => false,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    echo json_encode([
        'success' => true,
        'message' => "Заявка #{$ticket['number']} успешно назначена на {$executor['full_name']}"
    ]);
    exit;
}

if ($action === 'mass-assign' || $action === 'bulk-assign') {
    $ticketIds = $input['ticket_ids'] ?? [];
    $executorId = $input['executor_id'] ?? null;

    if (empty($ticketIds) || !$executorId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Выберите хотя бы одну заявку и указать исполнителя']);
        exit;
    }

    $executor = $storage->getById('users', $executorId);
    if (!$executor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Исполнитель не найден']);
        exit;
    }

    $count = 0;
    foreach ($ticketIds as $tId) {
        $ticket = $storage->getById('tickets', $tId);
        if ($ticket) {
            $storage->update('tickets', $tId, [
                'assigned_to' => (int)$executorId,
                'status' => 'assigned',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $storage->insert('ticket_comments', [
                'ticket_id' => (int)$tId,
                'user_id' => $currentUser['id'],
                'user_name' => $currentUser['full_name'],
                'role' => $currentUser['role'],
                'comment' => "Массовое назначение исполнителя: " . $executor['full_name'],
                'attachments' => [],
                'is_completion_report' => false,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $count++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Успешно назначено {$count} заявок на {$executor['full_name']}"
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Недействительное действие']);
