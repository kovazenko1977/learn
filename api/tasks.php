<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/SLAProvider.php';
require_once __DIR__ . '/../includes/Notifier.php';

$storage = Storage::getInstance();
$settings = $storage->getSettings();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// All task endpoints are now protected
$user = Auth::authenticate();

if ($method === 'POST' && $action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);
    $tasks = $storage->read('tasks');

    $newTask = [
        'id' => time() . rand(100, 999),
        'status' => 'New',
        'priority' => $data['priority'] ?? 'Medium',
        'created_at' => date('Y-m-d H:i:s'),
        'creator' => $user['full_name'],
        'creator_id' => $user['id'],
        'fields' => $data,
        'assigned_to' => null,
        'comments' => [],
        'history' => [
            ['at' => date('Y-m-d H:i:s'), 'by' => $user['full_name'], 'msg' => 'Task created']
        ]
    ];

    $newTask['deadline'] = SLAProvider::calculateDeadline($newTask['created_at'], $newTask['priority'], $settings['business_hours']);

    $tasks[] = $newTask;
    $storage->write('tasks', $tasks);

    echo json_encode(['success' => true, 'id' => $newTask['id']]);
    exit;
}

if ($method === 'GET') {
    $tasks = $storage->read('tasks');

    // Filter by role
    if ($user['role'] === 'Executor') {
        $tasks = array_filter($tasks, fn($t) => $t['assigned_to'] == $user['id']);
    } elseif ($user['role'] === 'Responsible Employee') {
        // Responsible employee sees tasks they created (this mock needs creator ID)
        // For now, let's say they see all for simplicity or filter by some field
    }

    echo json_encode(array_values($tasks));
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'update_status') {
        $taskId = $data['id'];
        $newStatus = $data['status'];

        $storage->atomicUpdate('tasks', function($tasks) use ($taskId, $newStatus, $user) {
            foreach ($tasks as &$t) {
                if ($t['id'] == $taskId) {
                    $oldStatus = $t['status'];
                    $t['status'] = $newStatus;
                    $t['history'][] = [
                        'at' => date('Y-m-d H:i:s'),
                        'by' => $user['full_name'],
                        'msg' => "Status changed from $oldStatus to $newStatus"
                    ];
                    Notifier::notifyStatusChange($taskId, $oldStatus, $newStatus);
                    break;
                }
            }
            return $tasks;
        });
        echo json_encode(['success' => true]);
    } elseif ($action === 'assign') {
        Auth::checkRole($user, ['Administrator', 'Head of Department']);
        $taskId = $data['id'];
        $executorId = $data['executor_id'];
        $users = $storage->read('users');
        $executorName = 'Unknown';
        foreach($users as $u) if($u['id'] == $executorId) $executorName = $u['full_name'];

        $storage->atomicUpdate('tasks', function($tasks) use ($taskId, $executorId, $executorName, $user) {
            foreach ($tasks as &$t) {
                if ($t['id'] == $taskId) {
                    $t['assigned_to'] = $executorId;
                    $t['history'][] = [
                        'at' => date('Y-m-d H:i:s'),
                        'by' => $user['full_name'],
                        'msg' => "Assigned to $executorName"
                    ];
                    break;
                }
            }
            return $tasks;
        });
        echo json_encode(['success' => true]);
    } elseif ($action === 'add_comment') {
        $taskId = $data['id'];
        $comment = $data['comment'];

        $storage->atomicUpdate('tasks', function($tasks) use ($taskId, $comment, $user) {
            foreach ($tasks as &$t) {
                if ($t['id'] == $taskId) {
                    $t['comments'][] = [
                        'at' => date('Y-m-d H:i:s'),
                        'by' => $user['full_name'],
                        'text' => $comment
                    ];
                    break;
                }
            }
            return $tasks;
        });
        echo json_encode(['success' => true]);
    }
}
