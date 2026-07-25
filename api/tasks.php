<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/TokenProvider.php';
require_once __DIR__ . '/../includes/SLAProvider.php';
require_once __DIR__ . '/../includes/Notifier.php';

$settings = json_decode(file_get_contents(__DIR__ . '/../data/settings.json'), true);
$storage = new Storage($settings);
$tokenProvider = new TokenProvider();
$slaProvider = new SLAProvider($settings);
$notifier = new Notifier($settings);

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);
$userData = $tokenProvider->validateToken($token);

if (!$userData) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    $tasks = $storage->getTasks();
    $role = $userData['role'];
    $userId = $userData['id'];
    $dept = $userData['department'];

    // Visibility filtering
    $filtered = array_filter($tasks, function($t) use ($role, $userId, $dept) {
        if ($role === 'admin') return true;
        if ($role === 'Department Head') return $t['creator_department'] === $dept;
        if ($role === 'Executor') return $t['assigned_to'] == $userId;
        if ($role === 'Responsible Employee') return $t['creator_id'] == $userId;
        return false;
    });

    if ($id) {
        foreach ($filtered as $t) {
            if ($t['id'] == $id) {
                $comments = array_values(array_filter($storage->getComments(), function($c) use ($id) {
                    return $c['task_id'] == $id;
                }));
                $t['comments'] = $comments;
                echo json_encode($t);
                exit;
            }
        }
        http_response_code(404);
        exit;
    }

    echo json_encode(array_values($filtered));
} elseif ($method === 'POST') {
    if ($action === 'comment') {
        $comments = $storage->getComments();
        $newComment = [
            'id' => count($comments) + 1,
            'task_id' => $input['task_id'],
            'user_id' => $userData['id'],
            'content' => $input['content'],
            'attachments' => $input['attachments'] ?? [],
            'created_at' => date('Y-m-d H:i:s')
        ];
        $comments[] = $newComment;
        $storage->saveComments($comments);

        $storage->addAuditLog($input['task_id'], $userData['id'], "Добавлен комментарий");

        echo json_encode(['success' => true, 'comment' => $newComment]);
        exit;
    }

    if ($action === 'mass_assign') {
        if ($userData['role'] !== 'admin' && $userData['role'] !== 'Department Head') {
            http_response_code(403); exit;
        }
        $tasks = $storage->getTasks();
        foreach ($tasks as &$t) {
            if (in_array($t['id'], $input['ids'])) {
                $t['assigned_to'] = $input['executor_id'];
                $t['status'] = 'assigned';
                $storage->addAuditLog($t['id'], $userData['id'], "Массовое назначение исполнителя (ID: {$input['executor_id']})");
            }
        }
        $storage->saveTasks($tasks);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'add_subtask') {
        $taskId = $input['task_id'];
        $title = $input['title'];
        $st = $storage->addSubtask($taskId, $title);
        $storage->addAuditLog($taskId, $userData['id'], "Добавлена подзадача: " . $title);
        echo json_encode(['success' => true, 'subtask' => $st]);
        exit;
    }

    if ($action === 'toggle_subtask') {
        $taskId = $input['task_id'];
        $subtaskId = $input['subtask_id'];
        $completed = $input['completed'];
        $storage->toggleSubtask($taskId, $subtaskId, $completed);
        $storage->addAuditLog($taskId, $userData['id'], "Статус подзадачи изменен на: " . ($completed ? "Выполнено" : "Новая"));
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'add_work_log') {
        $taskId = $input['task_id'];
        $hours = $input['hours'];
        $notes = $input['notes'] ?? '';
        $wl = $storage->addWorkLog($taskId, $userData['id'], $hours, $notes);
        $storage->addAuditLog($taskId, $userData['id'], "Зафиксировано {$hours} ч. работы: {$notes}");
        echo json_encode(['success' => true, 'work_log' => $wl]);
        exit;
    }

    if ($action === 'add_feedback') {
        $taskId = $input['task_id'];
        $rating = $input['rating'];
        $comment = $input['comment'] ?? '';
        $fb = $storage->addFeedback($taskId, $rating, $comment);
        $storage->addAuditLog($taskId, $userData['id'], "Добавлен отзыв с оценкой {$rating} звезд(ы)");
        echo json_encode(['success' => true, 'feedback' => $fb]);
        exit;
    }

    // Create task
    $tasks = $storage->getTasks();
    $createdAt = date('Y-m-d H:i:s');
    $deadline = $slaProvider->calculateDeadline($input['priority'], $createdAt);

    $newTask = [
        'id' => count($tasks) + 1,
        'title' => $input['title'],
        'description' => $input['description'],
        'category' => $input['category'],
        'priority' => $input['priority'],
        'status' => 'new',
        'creator_id' => $userData['id'],
        'creator_department' => $userData['department'],
        'assigned_to' => null,
        'completed_at' => null,
        'attachments' => $input['attachments'] ?? [],
        'custom_fields' => $input['custom_fields'] ?? [],
        'tags' => $input['tags'] ?? [],
        'created_at' => $createdAt,
        'deadline' => $deadline,
        'subtasks' => [],
        'work_logs' => [],
        'audit_logs' => [],
        'feedback' => null
    ];
    $tasks[] = $newTask;
    $storage->saveTasks($tasks);

    $storage->addAuditLog($newTask['id'], $userData['id'], "Заявка создана");
    $notifier->notify($userData['id'], "New Task Created", "Your task '{$newTask['title']}' has been created.");

    echo json_encode(['success' => true, 'task' => $newTask]);
} elseif ($method === 'PUT') {
    $tasks = $storage->getTasks();
    foreach ($tasks as &$t) {
        if ($t['id'] == $id) {
            $role = $userData['role'];
            $userId = $userData['id'];
            $dept = $userData['department'];

            // Secure RBAC check to prevent IDOR and unauthorized edits
            if ($role !== 'admin') {
                if ($role === 'Department Head' && $t['creator_department'] !== $dept) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Access denied: not your department']);
                    exit;
                }
                if ($role === 'Executor' && $t['assigned_to'] != $userId) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Access denied: task not assigned to you']);
                    exit;
                }
                if ($role === 'Responsible Employee') {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Access denied: Responsible Employees cannot update tasks directly']);
                    exit;
                }
            }

            if (isset($input['status'])) {
                $oldStatus = $t['status'];
                $t['status'] = $input['status'];
                if ($input['status'] === 'done' || $input['status'] === 'rejected') {
                    $t['completed_at'] = date('Y-m-d H:i:s');
                }
                $storage->addAuditLog($t['id'], $userData['id'], "Статус изменен с '{$oldStatus}' на '{$input['status']}'");
            }
            if (isset($input['assigned_to'])) {
                if ($role !== 'admin' && $role !== 'Department Head') {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Access denied: only managers can assign tasks']);
                    exit;
                }
                $t['assigned_to'] = $input['assigned_to'];
                if ($t['status'] === 'new') $t['status'] = 'assigned';
                $storage->addAuditLog($t['id'], $userData['id'], "Назначен исполнитель ID: {$input['assigned_to']}");
            }
            if (isset($input['tags'])) {
                $t['tags'] = $input['tags'];
                $storage->addAuditLog($t['id'], $userData['id'], "Обновлены теги");
            }

            $storage->saveTasks($tasks);
            echo json_encode(['success' => true]);
            exit;
        }
    }
}
