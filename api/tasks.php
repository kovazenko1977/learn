<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';

Auth::requireAuth();
$currentUser = Auth::getUser();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $tasks = Storage::read('tasks');
    // For admins: all tasks, for managers: assigned to them or created by them
    if ($currentUser['role'] !== 'admin') {
        $tasks = array_filter($tasks, function($t) use ($currentUser) {
            return ($t['assigned_to'] ?? '') === $currentUser['id'] ||
                   ($t['assigned_by'] ?? '') === $currentUser['id'];
        });
    }
    echo json_encode(array_values($tasks));
} elseif ($method === 'POST') {
    $data = Security::sanitize(json_decode(file_get_contents('php://input'), true));
    $tasks = Storage::read('tasks');

    if (isset($data['id'])) {
        foreach ($tasks as &$task) {
            if ($task['id'] === $data['id']) {
                $task = array_merge($task, $data);
                $task['updated_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
    } else {
        $data['id'] = uniqid('t_');
        $data['assigned_by'] = $currentUser['id'];
        $data['assigned_by_name'] = $currentUser['name'];
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 'pending';
        $tasks[] = $data;
    }

    Storage::save('tasks', $tasks);
    echo json_encode(['success' => true]);
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    $tasks = array_filter(Storage::read('tasks'), function($t) use ($id) { return $t['id'] !== $id; });
    Storage::save('tasks', array_values($tasks));
    echo json_encode(['success' => true]);
}
