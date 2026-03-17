<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Achievements.php';

if (!Auth::check()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$storage = new Storage('tasks.json');
$shoppingStorage = new Storage('shopping.json');
$achievementsStorage = new Storage('achievements.json');
$user = Auth::user();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $tasks = $storage->read();
    echo json_encode($tasks);
    exit;
}

if ($action === 'create') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';

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
                    if ($sub['completed']) {
                        $ach = new Achievements();
                        $ach->addProgress($user['id'], 'tasks_completed');
                    }
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

// --- Shopping ---
if ($action === 'shopping_list') {
    echo json_encode($shoppingStorage->read());
    exit;
}

if ($action === 'shopping_add') {
    $text = $_POST['text'] ?? '';
    if (empty($text)) exit;
    $items = $shoppingStorage->read();
    $items[] = ['id' => uniqid(), 'text' => $text, 'checked' => false, 'user_id' => $user['id']];
    $shoppingStorage->write($items);

    $ach = new Achievements();
    $ach->addProgress($user['id'], 'shopping_items_added');

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'shopping_toggle') {
    $id = $_POST['id'] ?? '';
    $items = $shoppingStorage->read();
    foreach ($items as &$item) {
        if ($item['id'] === $id) $item['checked'] = !$item['checked'];
    }
    $shoppingStorage->write($items);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'shopping_delete') {
    $id = $_POST['id'] ?? '';
    $items = $shoppingStorage->read();
    $items = array_values(array_filter($items, fn($i) => $i['id'] !== $id));
    $shoppingStorage->write($items);
    echo json_encode(['success' => true]);
    exit;
}

// --- Achievements ---
if ($action === 'achievements') {
    $ach = new Achievements();
    echo json_encode($ach->getUserAchievements($user['id']));
    exit;
}
