<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$storage = Storage::getInstance();
$currentUser = Auth::requireUser();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    $tickets = $storage->get('tickets');
    $categories = $storage->get('categories');
    $priorities = $storage->get('priorities');
    $users = $storage->get('users');

    // Index maps for fast lookup
    $catMap = [];
    foreach ($categories as $c) $catMap[$c['id']] = $c;

    $prioMap = [];
    foreach ($priorities as $p) $prioMap[$p['id']] = $p;

    $userMap = [];
    foreach ($users as $u) {
        unset($u['password']);
        $userMap[$u['id']] = $u;
    }

    $now = new DateTime();

    // Filter tickets according to RBAC
    $filteredTickets = [];
    foreach ($tickets as $t) {
        // Role restrictions:
        // - responsible: only created by self
        // - executor: assigned to self
        // - manager: all in department or all if unrestricted
        // - admin: all tickets
        if ($currentUser['role'] === 'responsible' && ($t['created_by'] ?? null) != $currentUser['id']) {
            continue;
        }
        if ($currentUser['role'] === 'executor' && ($t['assigned_to'] ?? null) != $currentUser['id']) {
            continue;
        }
        if ($currentUser['role'] === 'manager') {
            if (!empty($currentUser['department']) && !empty($t['department']) && $t['department'] !== $currentUser['department']) {
                // allow manager to view department requests
                // continue; // optional: enable strict department filtering if required
            }
        }

        // Apply URL Filters
        if (isset($_GET['status']) && $_GET['status'] !== '' && $t['status'] !== $_GET['status']) {
            continue;
        }
        if (isset($_GET['category_id']) && $_GET['category_id'] !== '' && $t['category_id'] != $_GET['category_id']) {
            continue;
        }
        if (isset($_GET['priority_id']) && $_GET['priority_id'] !== '' && $t['priority_id'] != $_GET['priority_id']) {
            continue;
        }
        if (isset($_GET['assigned_to']) && $_GET['assigned_to'] !== '' && $t['assigned_to'] != $_GET['assigned_to']) {
            continue;
        }
        if (isset($_GET['search']) && $_GET['search'] !== '') {
            $s = mb_strtolower($_GET['search']);
            $match = (mb_strpos(mb_strtolower($t['number'] ?? ''), $s) !== false) ||
                     (mb_strpos(mb_strtolower($t['title'] ?? ''), $s) !== false) ||
                     (mb_strpos(mb_strtolower($t['description'] ?? ''), $s) !== false);
            if (!$match) continue;
        }
        if (isset($_GET['archived']) && $_GET['archived'] === '1' && !($t['archived'] ?? false)) {
            continue;
        }
        if ((!isset($_GET['archived']) || $_GET['archived'] !== '1') && ($t['archived'] ?? false)) {
            continue;
        }

        // Enrich ticket metadata
        $t['category'] = $catMap[$t['category_id']] ?? null;
        $t['priority'] = $prioMap[$t['priority_id']] ?? null;
        $t['creator'] = $userMap[$t['created_by']] ?? null;
        $t['assignee'] = $t['assigned_to'] ? ($userMap[$t['assigned_to']] ?? null) : null;

        // SLA status calculation
        $slaStatus = 'ok'; // ok, warning, breached, completed
        if ($t['status'] === 'completed') {
            $slaStatus = 'completed';
        } elseif (!empty($t['due_date'])) {
            $dueDate = new DateTime($t['due_date']);
            if ($now > $dueDate) {
                $slaStatus = 'breached';
            } else {
                $createdDate = new DateTime($t['created_at']);
                $totalSec = $dueDate->getTimestamp() - $createdDate->getTimestamp();
                $remainingSec = $dueDate->getTimestamp() - $now->getTimestamp();
                if ($totalSec > 0 && ($remainingSec / $totalSec) <= 0.20) {
                    $slaStatus = 'warning';
                }
            }
        }
        $t['sla_status'] = $slaStatus;

        $filteredTickets[] = $t;
    }

    // Sort by updated_at desc
    usort($filteredTickets, fn($a, $b) => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));

    echo json_encode(['success' => true, 'tickets' => array_values($filteredTickets)]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if ($method === 'POST') {
    // 1. Create Ticket
    if ($action === '' || $action === 'create') {
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $categoryId = (int)($input['category_id'] ?? 1);
        $priorityId = (int)($input['priority_id'] ?? 2);
        $customFields = $input['custom_fields'] ?? [];
        $attachments = $input['attachments'] ?? [];

        if (!$title || !$description) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Заполните название и описание заявки']);
            exit;
        }

        // Calculate due_date from Priority SLA
        $priority = $storage->getById('priorities', $priorityId);
        $slaHours = $priority['sla_hours'] ?? 24;
        $createdAt = date('Y-m-d H:i:s');
        $dueDate = date('Y-m-d H:i:s', strtotime("+{$slaHours} hours"));

        // Generate Ticket Number (REQ-YYYY-XXXX)
        $allTickets = $storage->get('tickets');
        $nextNum = count($allTickets) + 1;
        $ticketNumber = 'REQ-' . date('Y') . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        $newTicket = [
            'number' => $ticketNumber,
            'title' => $title,
            'description' => $description,
            'category_id' => $categoryId,
            'priority_id' => $priorityId,
            'status' => 'new',
            'created_by' => $currentUser['id'],
            'assigned_to' => null,
            'department' => $currentUser['department'] ?? '',
            'custom_fields' => $customFields,
            'attachments' => $attachments,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'due_date' => $dueDate,
            'completed_at' => null,
            'archived' => false
        ];

        $createdTicket = $storage->insert('tickets', $newTicket);

        echo json_encode([
            'success' => true,
            'message' => 'Заявка успешно создана',
            'ticket' => $createdTicket
        ]);
        exit;
    }

    // 2. Update Ticket Status
    if ($action === 'update-status') {
        $id = $input['id'] ?? null;
        $status = trim($input['status'] ?? '');

        $validStatuses = ['new', 'assigned', 'in_progress', 'completed', 'rejected'];
        if (!$id || !in_array($status, $validStatuses)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Указан неверный статус или ID']);
            exit;
        }

        $ticket = $storage->getById('tickets', $id);
        if (!$ticket) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Заявка не найдена']);
            exit;
        }

        // Permission check
        if ($currentUser['role'] === 'executor' && $ticket['assigned_to'] != $currentUser['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Вы не можете менять статус чужой заявки']);
            exit;
        }

        $updateData = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($status === 'completed') {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        }

        $storage->update('tickets', $id, $updateData);

        // Add automated audit comment
        $statusLabels = [
            'new' => 'Новая',
            'assigned' => 'Назначена',
            'in_progress' => 'В работе',
            'completed' => 'Выполнена',
            'rejected' => 'Отклонена'
        ];
        $storage->insert('ticket_comments', [
            'ticket_id' => (int)$id,
            'user_id' => $currentUser['id'],
            'user_name' => $currentUser['full_name'],
            'role' => $currentUser['role'],
            'comment' => "Изменён статус заявки на: «" . ($statusLabels[$status] ?? $status) . "»",
            'attachments' => [],
            'is_completion_report' => ($status === 'completed'),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        echo json_encode(['success' => true, 'message' => 'Статус заявки обновлён']);
        exit;
    }

    // 3. Archive/Unarchive Ticket
    if ($action === 'toggle-archive') {
        $id = $input['id'] ?? null;
        $ticket = $storage->getById('tickets', $id);
        if (!$ticket) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Заявка не найдена']);
            exit;
        }

        $newArchivedState = !($ticket['archived'] ?? false);
        $storage->update('tickets', $id, [
            'archived' => $newArchivedState,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        echo json_encode([
            'success' => true,
            'message' => $newArchivedState ? 'Заявка перенесена в архив' : 'Заявка извлечена из архива'
        ]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Недействительный запрос']);
