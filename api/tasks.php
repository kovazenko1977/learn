<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/TokenProvider.php';
require_once __DIR__ . '/../includes/SLAProvider.php';
require_once __DIR__ . '/../includes/Notifier.php';
require_once __DIR__ . '/../includes/Permissions.php';

$storage = new Storage();
$currentUser = TokenProvider::getCurrentUser();

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Banned check
$currentUserFull = $storage->getById('users', $currentUser['id']);
if ($currentUserFull && !empty($currentUserFull['banned'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'User is banned', 'banned' => true]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

if ($method === 'GET') {
    if ($id) {
        $task = $storage->getById('tasks', $id);
        if (!$task) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Task not found']);
            exit;
        }

        // Granular check: can view all tasks?
        if (!Permissions::check($currentUserFull, 'can_view_all_tasks', $storage)) {
            if (($task['creator_id'] ?? '') !== $currentUser['id'] && ($task['executor_id'] ?? '') !== $currentUser['id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden']);
                exit;
            }
        }

        $comments = $storage->getAll('comments');
        $task['comments'] = array_filter($comments, fn($c) => $c['task_id'] === $id);
        echo json_encode($task);
    } else {
        $tasks = $storage->getAll('tasks');

        // Granular filter: can view all tasks?
        if (!Permissions::check($currentUserFull, 'can_view_all_tasks', $storage)) {
            $tasks = array_filter($tasks, function($t) use ($currentUser) {
                return ($t['creator_id'] ?? '') === $currentUser['id'] || ($t['executor_id'] ?? '') === $currentUser['id'];
            });
        }
        echo json_encode(array_values($tasks));
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($id && isset($data['content'])) { // Add comment
        if (!Permissions::check($currentUserFull, 'can_comment_tasks', $storage)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Вам запрещено добавлять комментарии']);
            exit;
        }

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
            // Check can_create_tasks
            if (!Permissions::check($currentUserFull, 'can_create_tasks', $storage)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Вам запрещено создавать заявки']);
                exit;
            }

            $data['creator_id'] = $currentUser['id'];
            $data['status'] = 'new';
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['deadline'] = SLAProvider::calculateDeadline($data['priority'] ?? 'Средний', $data['created_at']);
        }

        // Permission check for updates
        if ($id) {
            $existing = $storage->getById('tasks', $id);
            if (!$existing) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                exit;
            }

            // Check if they are allowed to assign executors or edit fields
            if (isset($data['executor_id']) && $data['executor_id'] !== ($existing['executor_id'] ?? '')) {
                if (!Permissions::check($currentUserFull, 'can_assign_executors', $storage)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Вам запрещено назначать исполнителей']);
                    exit;
                }
            }

            if (!Permissions::check($currentUserFull, 'can_edit_tasks', $storage)) {
                // If they can't edit tasks generally, but they are Executor, can they change status?
                // Yes, Executors can change task status of assigned tasks
                if ($currentUser['role'] === 'Executor' && isset($data['status']) && ($existing['executor_id'] ?? '') === $currentUser['id']) {
                    $existing['status'] = $data['status'];
                    if ($data['status'] === 'done' && !isset($existing['completed_at'])) {
                        $existing['completed_at'] = date('Y-m-d H:i:s');
                    }
                    $data = $existing;
                } else {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Вам запрещено редактировать заявки']);
                    exit;
                }
            } else {
                if (isset($data['status']) && $data['status'] === 'done' && !isset($existing['completed_at'])) {
                    $data['completed_at'] = date('Y-m-d H:i:s');
                }
                $data = array_merge($existing, $data);
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
} elseif ($method === 'DELETE') {
    if (!Permissions::check($currentUserFull, 'can_delete_tasks', $storage)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Вам запрещено удалять заявки']);
        exit;
    }
    if ($id) {
        $storage->delete('tasks', $id);
        echo json_encode(['success' => true]);
    }
}
