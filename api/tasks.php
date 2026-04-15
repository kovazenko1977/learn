<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';

Auth::requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(Storage::read('tasks'));
} elseif ($method === 'POST') {
    $data = Security::sanitize(json_decode(file_get_contents('php://input'), true));
    $tasks = Storage::read('tasks');

    if (isset($data['id'])) {
        foreach ($tasks as &$task) {
            if ($task['id'] === $data['id']) {
                $task = array_merge($task, $data);
                break;
            }
        }
    } else {
        $data['id'] = uniqid();
        $data['created_at'] = date('Y-m-d H:i:s');
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
