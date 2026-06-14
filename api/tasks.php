<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/SLAProvider.php';
require_once __DIR__ . '/../includes/Notifier.php';

header('Content-Type: application/json');

$settingsFile = __DIR__ . '/../data/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];

$storage = new Storage($settings);
$auth = new Auth($storage, $settings);
$sla = new SLAProvider($settings);
$notifier = new Notifier($settings);

$user = $auth->getUser();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    $auth->requireAuth();

    if ($action === 'history') {
        $taskId = $_GET['task_id'] ?? 0;
        $taskHistory = $storage->get('history', ['task_id' => $taskId]);

        $users = $storage->get('users');
        $userMap = [];
        foreach ($users as $u) $userMap[$u['id']] = $u['full_name'] ?: $u['username'];

        foreach ($taskHistory as &$h) {
            $h['user_name'] = $userMap[$h['user_id']] ?? 'Система';
        }

        echo json_encode(array_values($taskHistory));
        exit;
    }

    if ($action === 'comments') {
        $taskId = $_GET['task_id'] ?? 0;
        $taskComments = $storage->get('comments', ['task_id' => $taskId]);

        $users = $storage->get('users');
        $userMap = [];
        foreach ($users as $u) $userMap[$u['id']] = $u['full_name'] ?: $u['username'];

        foreach ($taskComments as &$c) {
            $c['user_name'] = $userMap[$c['user_id']] ?? 'Unknown';
        }

        echo json_encode(array_values($taskComments));
        exit;
    }

    $tasks = [];
    // RBAC Filtering
    if ($user['role'] === 'responsible') {
        $tasks = $storage->get('tasks', ['creator_id' => $user['id']]);
    } elseif ($user['role'] === 'executor') {
        $tasks = $storage->get('tasks', ['executor_id' => $user['id']]);
    } else {
        $tasks = $storage->get('tasks');
    }

    echo json_encode(array_values($tasks));
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'comment') {
        $auth->requireAuth();
        $comment = [
            'task_id' => $input['task_id'],
            'user_id' => $user['id'],
            'text' => $input['text'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        $storage->save('comments', $comment);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'mass_assign') {
        $auth->requireRole(['admin', 'head']);
        $taskIds = $input['ids'] ?? [];
        $executorId = $input['executor_id'];

        foreach ($taskIds as $id) {
            $task = $storage->find('tasks', $id);
            if ($task) {
                $task['executor_id'] = $executorId;
                $task['status'] = 'assigned';
                $storage->save('tasks', $task);
            }
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if (!isset($input['id'])) {
        $input['creator_id'] = $user ? $user['id'] : 0;
        $input['status'] = 'new';
        $input['deadline'] = $sla->calculateDeadline($input['priority'] ?? 'medium');
        $input['created_at'] = date('Y-m-d H:i:s');
    } else {
        $auth->requireAuth();
        $existing = $storage->find('tasks', $input['id']);
        if (!$existing) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Task not found']);
            exit;
        }

        if (isset($input['status']) && $input['status'] !== $existing['status']) {
            $notifier->notifyStatusChange(array_merge($existing, $input), $input['status']);

            $history = [
                'task_id' => $existing['id'],
                'user_id' => $user['id'],
                'old_status' => $existing['status'],
                'new_status' => $input['status'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            $storage->save('history', $history);

            if ($input['status'] === 'completed') {
                $input['completed_at'] = date('Y-m-d H:i:s');
            }
        }
    }

    $id = $storage->save('tasks', $input);
    echo json_encode(['id' => $id, 'success' => true]);
}
