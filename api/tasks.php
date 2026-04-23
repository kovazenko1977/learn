<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/SLAProvider.php';
require_once __DIR__ . '/../includes/Security.php';

Auth::requireAuth();
$currentUser = Auth::getUser();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $tasks = Storage::read('tasks');
    // Manager only sees their own or unassigned
    if ($currentUser['role'] !== 'admin') {
        $tasks = array_filter($tasks, function($t) use ($currentUser) {
            return $t['assigned_to'] === $currentUser['id'] || $t['assigned_by'] === $currentUser['id'];
        });
    }
    echo json_encode(array_values($tasks));
} elseif ($method === 'POST') {
    $data = Security::sanitize(json_decode(file_get_contents('php://input'), true));
    $tasks = Storage::read('tasks');

    if (isset($data['id'])) {
        foreach ($tasks as &$task) {
            if ($task['id'] === $data['id']) {
                $oldStatus = $task['status'];
                $task = array_merge($task, $data);
                if ($oldStatus !== 'completed' && $task['status'] === 'completed') {
                    Storage::addPoints($currentUser['id'], 15);
                    Storage::log("Completed task: " . $task['title'], $currentUser['id']);
                }
                break;
            }
        }
    } else {
        $data['id'] = uniqid('task_');
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['assigned_by'] = $currentUser['id'];
        if (empty($data['assigned_to'])) $data['assigned_to'] = $currentUser['id'];
        $tasks[] = $data;
        Storage::log("Created task: " . $data['title'], $currentUser['id']);
    }

    Storage::save('tasks', $tasks);
    echo json_encode(['success' => true]);
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    $tasks = Storage::read('tasks');
    $tasks = array_filter($tasks, function($t) use ($id) { return $t['id'] !== $id; });
    Storage::save('tasks', array_values($tasks));
    echo json_encode(['success' => true]);
}
