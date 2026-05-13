<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Notifier.php';
require_once __DIR__ . '/../includes/SLAProvider.php';

$user = Auth::authenticate();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    $tasks = Storage::read('tasks');

    // RBAC Filtering
    if ($user['role'] === 'Executor') {
        $tasks = array_filter($tasks, fn($t) => $t['executor_id'] === $user['id']);
    } elseif ($user['role'] === 'Responsible Employee') {
        $tasks = array_filter($tasks, fn($t) => $t['creator_id'] === $user['id']);
    } elseif ($user['role'] === 'Head of Department') {
        $tasks = array_filter($tasks, fn($t) => ($t['department'] ?? '') === ($user['department'] ?? ''));
    }

    foreach ($tasks as &$t) {
        $t['is_overdue'] = SLAProvider::isOverdue($t);
    }

    echo json_encode(array_values($tasks));
} elseif ($method === 'POST') {
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    if ($action === 'create') {
        Auth::checkRole($user, ['Administrator', 'Responsible Employee']);
        $createdAt = date('Y-m-d H:i:s');
        $newTask = [
            'id' => Storage::generateId(),
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'category' => $data['category'] ?? '',
            'priority' => $data['priority'] ?? 'Medium',
            'custom_fields' => $data['custom_fields'] ?? (object)[],
            'status' => 'New',
            'department' => $user['department'] ?? 'General',
            'creator_id' => $user['id'],
            'creator_name' => $user['name'],
            'created_at' => $createdAt,
            'deadline' => SLAProvider::getDeadline($data['priority'] ?? 'Medium', $createdAt),
            'executor_id' => null,
            'executor_name' => null,
            'comments' => [],
            'attachments' => [],
            'history' => [
                ['user' => $user['name'], 'action' => 'CREATED', 'time' => date('Y-m-d H:i:s')]
            ]
        ];
        $tasks = Storage::read('tasks');
        $tasks[] = $newTask;
        Storage::write('tasks', $tasks);
        echo json_encode($newTask);
    } elseif ($action === 'update_status') {
        $taskId = $data['id'] ?? '';
        $newStatus = $data['status'] ?? '';

        $tasks = Storage::read('tasks');
        $found = false;
        foreach ($tasks as &$task) {
            if ($task['id'] === $taskId) {
                $oldStatus = $task['status'];
                $task['status'] = $newStatus;
                $actionName = ($newStatus === 'Completed') ? 'COMPLETED' : "Status changed to $newStatus";
                $task['history'][] = [
                    'user' => $user['name'],
                    'action' => $actionName,
                    'time' => date('Y-m-d H:i:s')
                ];
                $found = true;
                break;
            }
        }
        if ($found) {
            Storage::write('tasks', $tasks);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Task not found']);
        }
    } elseif ($action === 'assign') {
        Auth::checkRole($user, ['Administrator', 'Head of Department']);
        $taskIds = is_array($data['id']) ? $data['id'] : [$data['id']];
        $executorId = $data['executor_id'] ?? '';

        $users = Storage::read('users');
        $executorName = 'Unknown';
        foreach ($users as $u) {
            if ($u['id'] === $executorId) {
                $executorName = $u['name'];
                break;
            }
        }

        $tasks = Storage::read('tasks');
        $updatedCount = 0;
        foreach ($tasks as &$task) {
            if (in_array($task['id'], $taskIds)) {
                $task['executor_id'] = $executorId;
                $task['executor_name'] = $executorName;
                if ($task['status'] === 'New') $task['status'] = 'Assigned';
                $task['history'][] = [
                    'user' => $user['name'],
                    'action' => "Assigned to $executorName",
                    'time' => date('Y-m-d H:i:s')
                ];
                $updatedCount++;
                Notifier::notify($executorId, "New task assigned: " . $task['title']);
            }
        }
        if ($updatedCount > 0) {
            Storage::write('tasks', $tasks);
            echo json_encode(['success' => true, 'updated' => $updatedCount]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Tasks not found']);
        }
    } elseif ($action === 'add_comment') {
        $taskId = $data['id'] ?? '';
        $commentText = $data['comment'] ?? '';

        $tasks = Storage::read('tasks');
        $found = false;
        foreach ($tasks as &$task) {
            if ($task['id'] === $taskId) {
                $comment = [
                    'id' => Storage::generateId(),
                    'user_id' => $user['id'],
                    'user_name' => $user['name'],
                    'text' => $commentText,
                    'time' => date('Y-m-d H:i:s')
                ];
                $task['comments'][] = $comment;
                $found = true;
                break;
            }
        }
        if ($found) {
            Storage::write('tasks', $tasks);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Task not found']);
        }
    } elseif ($action === 'upload') {
        $taskId = $_POST['task_id'] ?? '';
        $tasks = Storage::read('tasks');
        $found = false;
        foreach ($tasks as &$task) {
            if ($task['id'] === $taskId) {
                if (!isset($_FILES['file'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'No file uploaded']);
                    exit;
                }

                $file = $_FILES['file'];
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'zip', 'rar'];

                if (!in_array(strtolower($ext), $allowed)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid file type']);
                    exit;
                }

                // Mime type check (basic)
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $dangerous = ['application/x-php', 'text/php', 'application/javascript'];
                if (in_array($mime, $dangerous)) {
                     http_response_code(400);
                     echo json_encode(['error' => 'Dangerous file type']);
                     exit;
                }

                $newName = Storage::generateId() . '.' . $ext;
                $uploadDir = __DIR__ . '/../uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                    $task['attachments'][] = [
                        'name' => htmlspecialchars($file['name']),
                        'path' => 'uploads/' . $newName,
                        'user' => $user['name'],
                        'time' => date('Y-m-d H:i:s')
                    ];
                    $found = true;
                }
                break;
            }
        }
        if ($found) {
            Storage::write('tasks', $tasks);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Task not found']);
        }
    }
}
