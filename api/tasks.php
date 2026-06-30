<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/TokenProvider.php';
require_once __DIR__ . '/../includes/SLAProvider.php';
require_once __DIR__ . '/../includes/Notifier.php';

$storage = new Storage();
$currentUser = TokenProvider::getCurrentUser();

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

if ($method === 'GET') {
    if ($id) {
        $task = $storage->getById('tasks', $id);
        $comments = $storage->getAll('comments');
        $task['comments'] = array_values(array_filter($comments, fn($c) => $c['task_id'] === $id));
        echo json_encode($task);
    } else {
        $tasks = $storage->getAll('tasks');
        // Role based filtering
        if ($currentUser['role'] === 'Executor') {
            $tasks = array_filter($tasks, fn($t) => $t['executor_id'] === $currentUser['id']);
        } elseif ($currentUser['role'] === 'Responsible Employee') {
            $tasks = array_filter($tasks, fn($t) => $t['creator_id'] === $currentUser['id']);
        } elseif ($currentUser['role'] === 'Department Head') {
            $tasks = array_filter($tasks, fn($t) => ($t['department'] ?? '') === ($currentUser['department'] ?? ''));
        }
        echo json_encode(array_values($tasks));
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($id && isset($data['content'])) { // Add comment
        $comment = [
            'task_id' => $id,
            'user_id' => $currentUser['id'],
            'content' => $data['content'],
            'attachments' => $data['attachments'] ?? [],
            'created_at' => date('Y-m-d H:i:s')
        ];
        $storage->save('comments', $comment);
        echo json_encode(['success' => true]);
    } else { // Create/Update task
        if (!$id) {
            $data['creator_id'] = $currentUser['id'];
            $data['department'] = $currentUser['department'] ?? '';
            $data['status'] = 'new';
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['deadline'] = SLAProvider::calculateDeadline($data['priority'] ?? 'Средний', $data['created_at']);
        }

        // Permission check for updates
        if ($id) {
            $existing = $storage->getById('tasks', $id);
            if ($currentUser['role'] === 'Executor' && isset($data['status'])) {
                // Executor can only change status
                $existing['status'] = $data['status'];
                if ($data['status'] === 'done' && !isset($existing['completed_at'])) {
                    $existing['completed_at'] = date('Y-m-d H:i:s');
                }
                $data = $existing;
            } elseif ($currentUser['role'] === 'Department Head' || $currentUser['role'] === 'Administrator') {
                if (isset($data['status']) && $data['status'] === 'done' && !isset($existing['completed_at'])) {
                    $data['completed_at'] = date('Y-m-d H:i:s');
                }
                $data = array_merge($existing, $data);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden']);
                exit;
            }
        }

        $taskId = $storage->save('tasks', $data);

        // Notifications
        $updatedTask = $storage->getById('tasks', $taskId);
        if (!$id) {
            Notifier::notify('created', $updatedTask);
        } elseif (isset($data['status'])) {
            if ($data['status'] === 'assigned' && !empty($data['executor_id'])) {
                $executor = $storage->getById('users', $data['executor_id']);
                Notifier::notify('assignment', $updatedTask, $executor);
            } else {
                Notifier::notify('status_change', $updatedTask);
            }
        }

        echo json_encode(['success' => true, 'id' => $taskId]);
    }
} elseif ($method === 'DELETE' && ($currentUser['role'] === 'Administrator' || $currentUser['role'] === 'Department Head')) {
    if ($id) {
        $storage->delete('tasks', $id);
        echo json_encode(['success' => true]);
    }
}
