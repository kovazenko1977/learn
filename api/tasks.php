<?php
Auth::requireRole(['admin', 'senior_admin', 'manager', 'doctor']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        $task = Storage::read('tasks', $id);
        echo json_encode($task ?: ['error' => 'Task not found']);
    } else {
        $tasks = Storage::list('tasks');
        // Filter by role if needed
        if ($_SESSION['role'] === 'doctor' || $_SESSION['role'] === 'manager') {
            $tasks = array_filter($tasks, function($t) {
                return $t['assigned_to'] === $_SESSION['user_id'] || $t['created_by'] === $_SESSION['user_id'];
            });
            $tasks = array_values($tasks);
        }
        echo json_encode($tasks);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $input = Security::sanitize($input);
    $id = isset($input['id']) ? $input['id'] : uniqid();
    $input['id'] = $id;
    if (!isset($input['created_at'])) {
        $input['created_at'] = date('c');
        $input['created_by'] = $_SESSION['user_id'];
    }
    Storage::write('tasks', $id, $input);
    echo json_encode(['success' => true, 'id' => $id]);
} elseif ($method === 'DELETE') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        Storage::delete('tasks', $id);
        echo json_encode(['success' => true]);
    }
}
