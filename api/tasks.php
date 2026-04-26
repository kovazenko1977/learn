<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Notifier.php';

$user = Auth::getUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$tasks = Storage::get('tasks');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // ... list logic stays the same ...
    if ($action === 'list') {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $priority = $_GET['priority'] ?? '';

        $filtered = array_filter($tasks, function($t) use ($user, $search, $status, $priority) {
            $visible = false;
            if ($user['role'] === 'admin') $visible = true;
            elseif ($user['role'] === 'head') $visible = ($t['department'] === $user['department']);
            elseif ($user['role'] === 'executor') $visible = ($t['executor_id'] === $user['id']);
            elseif ($user['role'] === 'employee') $visible = ($t['creator_id'] === $user['id']);

            if (!$visible) return false;

            if ($search && stripos($t['title'], $search) === false && stripos($t['description'], $search) === false) return false;
            if ($status && $t['status'] !== $status) return false;
            if ($priority && $t['priority'] !== $priority) return false;

            return true;
        });
        echo json_encode(array_values($filtered));
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'create') {
        $newTask = [
            'id' => bin2hex(random_bytes(8)),
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'category' => $data['category'] ?? '',
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'new',
            'creator_id' => $user['id'],
            'creator_name' => $user['full_name'],
            'department' => $user['department'],
            'created_at' => date('Y-m-d H:i:s'),
            'comments' => [],
            'history' => [
                ['date' => date('Y-m-d H:i:s'), 'user' => $user['full_name'], 'action' => 'Заявка создана']
            ],
            'custom_fields' => $data['custom_fields'] ?? [],
            'attachments' => $data['attachments'] ?? []
        ];
        $tasks[] = $newTask;
        Storage::set('tasks', $tasks);
        Notifier::notify("Новая заявка: {$newTask['title']} от {$user['full_name']}");
        echo json_encode(['success' => true, 'task' => $newTask]);
    } elseif ($action === 'update_status') {
        $taskId = $data['id'];
        foreach ($tasks as &$t) {
            if ($t['id'] === $taskId) {
                if ($user['role'] === 'admin' || $user['role'] === 'head' || ($user['role'] === 'executor' && $t['executor_id'] == $user['id'])) {
                    $oldStatus = $t['status'];
                    $t['status'] = $data['status'];
                    $t['history'][] = [
                        'date' => date('Y-m-d H:i:s'),
                        'user' => $user['full_name'],
                        'action' => "Статус изменен: $oldStatus -> {$data['status']}"
                    ];
                    if ($data['status'] === 'completed') {
                        $t['completed_at'] = date('Y-m-d H:i:s');
                    }
                    Storage::set('tasks', $tasks);
                    Notifier::notify("Статус заявки #{$t['id']} изменен на {$data['status']}");
                    echo json_encode(['success' => true]);
                    exit;
                }
            }
        }
    } elseif ($action === 'assign') {
        if ($user['role'] !== 'admin' && $user['role'] !== 'head') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
        $taskId = $data['id'];
        foreach ($tasks as &$t) {
            if ($t['id'] === $taskId) {
                $t['executor_id'] = $data['executor_id'];
                $t['executor_name'] = $data['executor_name'];
                $t['status'] = 'assigned';
                $t['history'][] = [
                    'date' => date('Y-m-d H:i:s'),
                    'user' => $user['full_name'],
                    'action' => "Назначен исполнитель: {$data['executor_name']}"
                ];
                Storage::set('tasks', $tasks);
                Notifier::notify("Вам назначена заявка #{$t['id']}: {$t['title']}", 'task');
                echo json_encode(['success' => true]);
                exit;
            }
        }
    } elseif ($action === 'mass_assign') {
        if ($user['role'] !== 'admin' && $user['role'] !== 'head') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
        $taskIds = $data['ids'] ?? [];
        $executorId = $data['executor_id'];
        $executorName = $data['executor_name'];

        foreach ($tasks as &$t) {
            if (in_array($t['id'], $taskIds)) {
                $t['executor_id'] = $executorId;
                $t['executor_name'] = $executorName;
                $t['status'] = 'assigned';
                $t['history'][] = [
                    'date' => date('Y-m-d H:i:s'),
                    'user' => $user['full_name'],
                    'action' => "Массовое назначение исполнителя: $executorName"
                ];
            }
        }
        Storage::set('tasks', $tasks);
        Notifier::notify("Массовое назначение заявок для $executorName: " . count($taskIds) . " шт.");
        echo json_encode(['success' => true]);
    } elseif ($action === 'add_comment') {
        $taskId = $data['id'];
        foreach ($tasks as &$t) {
            if ($t['id'] === $taskId) {
                $t['comments'][] = [
                    'user' => $user['full_name'],
                    'text' => $data['text'],
                    'date' => date('Y-m-d H:i:s'),
                    'attachments' => $data['attachments'] ?? []
                ];
                Storage::set('tasks', $tasks);
                echo json_encode(['success' => true]);
                exit;
            }
        }
    }
}
