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

$auth->requireAuth();
$user = $auth->getUser();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'comments') {
        $taskId = $_GET['task_id'] ?? 0;
        $allComments = $storage->get('comments');
        $taskComments = array_filter($allComments, fn($c) => $c['task_id'] == $taskId);

        // Join with user names
        $users = $storage->get('users');
        $userMap = [];
        foreach ($users as $u) $userMap[$u['id']] = $u['full_name'];

        foreach ($taskComments as &$c) {
            $c['user_name'] = $userMap[$c['user_id']] ?? 'Unknown';
        }

        echo json_encode(array_values($taskComments));
        exit;
    }

    $tasks = $storage->get('tasks');

    // RBAC Filtering
    if ($user['role'] === 'responsible') {
        $tasks = array_filter($tasks, fn($t) => ($t['creator_id'] ?? 0) == $user['id']);
    } elseif ($user['role'] === 'executor') {
        $tasks = array_filter($tasks, fn($t) => ($t['executor_id'] ?? 0) == $user['id']);
    }

    echo json_encode(array_values($tasks));
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'comment') {
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

    // Creating new task
    if (!isset($input['id'])) {
        $input['creator_id'] = $user['id'];
        $input['status'] = 'new';
        $input['deadline'] = $sla->calculateDeadline($input['priority'] ?? 'medium');
    } else {
        // Update logic with RBAC
        $existing = $storage->find('tasks', $input['id']);
        if (!$existing) exit;

        if ($user['role'] === 'executor') {
            // Executor can only update status
            $input = [
                'id' => $existing['id'],
                'status' => $input['status']
            ];
        } elseif ($user['role'] === 'responsible' && $existing['creator_id'] != $user['id']) {
            exit;
        }

        if (isset($input['status']) && $input['status'] !== $existing['status']) {
            $notifier->notifyStatusChange($existing, $input['status']);
        }
    }

    $id = $storage->save('tasks', $input);
    echo json_encode(['id' => $id, 'success' => true]);
}
