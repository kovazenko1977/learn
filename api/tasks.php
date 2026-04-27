<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/SLAProvider.php';
require_once __DIR__ . '/../includes/Notifier.php';

$user = Auth::authenticate();
if (!$user) {
    http_response_code(401);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$tasks = Storage::getData('tasks');

if ($method === 'GET') {
    $filtered = $tasks;
    if ($user['role'] === 'Responsible Employee') {
        $filtered = array_filter($tasks, fn($t) => $t['created_by'] === $user['id']);
    } elseif ($user['role'] === 'Head of Department' || $user['role'] === 'Executor') {
        $filtered = array_filter($tasks, fn($t) => $t['department'] === $user['department']);
    }

    foreach ($filtered as &$t) {
        $t['sla_status'] = SLAProvider::getStatus($t);
    }

    echo json_encode(array_values($filtered));
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';

    if ($action === 'comment') {
        $taskId = $data['task_id'];
        foreach ($tasks as &$t) {
            if ($t['id'] === $taskId) {
                $t['comments'][] = [
                    'user_id' => $user['id'],
                    'user_name' => $user['full_name'],
                    'text' => $data['text'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                break;
            }
        }
    } elseif ($action === 'mass_assign' && Auth::checkRole(['Administrator', 'Head of Department'])) {
        $taskIds = $data['task_ids'];
        $executorId = $data['executor_id'];
        $executorName = $data['executor_name'];
        foreach ($tasks as &$t) {
            if (in_array($t['id'], $taskIds)) {
                $t['executor_id'] = $executorId;
                $t['executor_name'] = $executorName;
                $t['status'] = 'Assigned';
            }
        }
    } else {
        if (empty($data['id'])) {
            $data['id'] = bin2hex(random_bytes(8));
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['created_by'] = $user['id'];
            $data['created_by_name'] = $user['full_name'];
            $data['status'] = 'New';
            $data['comments'] = [];
            $data['deadline'] = SLAProvider::calculateDeadline($data['priority'], $data['created_at']);
            $tasks[] = $data;
        } else {
            foreach ($tasks as &$t) {
                if ($t['id'] === $data['id']) {
                    $oldStatus = $t['status'];
                    $t = array_merge($t, $data);
                    if ($t['status'] !== $oldStatus) {
                        Notifier::notifyStatusChange($t, $t['status']);
                    }
                    break;
                }
            }
        }
    }
    Storage::saveData('tasks', $tasks);
    echo json_encode(['success' => true]);
} elseif ($method === 'DELETE' && Auth::isAdmin()) {
    $id = $_GET['id'] ?? '';
    $tasks = array_filter($tasks, fn($t) => $t['id'] !== $id);
    Storage::saveData('tasks', array_values($tasks));
    echo json_encode(['success' => true]);
}