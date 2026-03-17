<?php
require_once '../includes/Auth.php';
require_once '../includes/Storage.php';

if (!Auth::check()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$storage = new Storage('tasks.json');
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $tasks = $storage->read();
    echo json_encode($tasks);
    exit;
}

if ($action === 'create') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $user = Auth::user();

    if (empty($title)) {
        echo json_encode(['success' => false, 'error' => 'Title required']);
        exit;
    }

    $tasks = $storage->read();
    $newTask = [
        'id' => uniqid(),
        'title' => $title,
        'description' => $description,
        'status' => 'new',
        'created_at' => time(),
        'created_by' => $user['id'],
        'username' => $user['username'],
        'subtasks' => [],
        'comments' => []
    ];
    $tasks[] = $newTask;
    $storage->write($tasks);
    echo json_encode(['success' => true, 'task' => $newTask]);
    exit;
}

if ($action === 'add_subtask') {
    $task_id = $_POST['task_id'] ?? '';
    $text = $_POST['text'] ?? '';
    if (empty($task_id) || empty($text)) exit;

    $tasks = $storage->read();
    foreach ($tasks as &$task) {
        if ($task['id'] === $task_id) {
            $task['subtasks'][] = [
                'id' => uniqid(),
                'text' => $text,
                'completed' => false
            ];
            break;
        }
    }
    $storage->write($tasks);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'toggle_subtask') {
    $task_id = $_POST['task_id'] ?? '';
    $subtask_id = $_POST['subtask_id'] ?? '';
    if (empty($task_id) || empty($subtask_id)) exit;

    $tasks = $storage->read();
    foreach ($tasks as &$task) {
        if ($task['id'] === $task_id) {
            foreach ($task['subtasks'] as &$sub) {
                if ($sub['id'] === $subtask_id) {
                    $sub['completed'] = !$sub['completed'];
                    break;
                }
            }
            break;
        }
    }
    $storage->write($tasks);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'add_comment') {
    $task_id = $_POST['task_id'] ?? '';
    $comment = $_POST['comment'] ?? '';
    $user = Auth::user();

    if (empty($task_id) || empty($comment)) exit;

    $tasks = $storage->read();
    foreach ($tasks as &$task) {
        if ($task['id'] === $task_id) {
            $task['comments'][] = [
                'id' => uniqid(),
                'user_id' => $user['id'],
                'username' => $user['username'],
                'text' => $comment,
                'timestamp' => time()
            ];
            break;
        }
    }
    $storage->write($tasks);
    echo json_encode(['success' => true]);
    exit;
}
