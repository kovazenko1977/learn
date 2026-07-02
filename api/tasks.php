<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/TokenProvider.php';
require_once __DIR__ . '/../includes/SLAProvider.php';
require_once __DIR__ . '/../includes/Notifier.php';

$settings = json_decode(file_get_contents(__DIR__ . '/../data/settings.json'), true);
$storage = new Storage($settings);
$tokenProvider = new TokenProvider();
$slaProvider = new SLAProvider($settings);
$notifier = new Notifier($settings);

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);
$userData = $tokenProvider->validateToken($token);

if (!$userData) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$id = $_GET['id'] ?? null;

if ($method === 'GET') {
    $tasks = $storage->getTasks();
    $role = $userData['role'];
    $userId = $userData['id'];
    $dept = $userData['department'];

    // Visibility filtering
    $filtered = array_filter($tasks, function($t) use ($role, $userId, $dept) {
        if ($role === 'admin') return true;
        if ($role === 'Department Head') return $t['creator_department'] === $dept;
        if ($role === 'Executor') return $t['assigned_to'] == $userId;
        if ($role === 'Responsible Employee') return $t['creator_id'] == $userId;
        return false;
    });

    if ($id) {
        foreach ($filtered as $t) {
            if ($t['id'] == $id) {
                $comments = array_values(array_filter($storage->getComments(), function($c) use ($id) {
                    return $c['task_id'] == $id;
                }));
                $t['comments'] = $comments;
                echo json_encode($t);
                exit;
            }
        }
        http_response_code(404);
        exit;
    }

    echo json_encode(array_values($filtered));
} elseif ($method === 'POST') {
    if (isset($_GET['action']) && $_GET['action'] === 'comment') {
        $comments = $storage->getComments();
        $newComment = [
            'id' => count($comments) + 1,
            'task_id' => $input['task_id'],
            'user_id' => $userData['id'],
            'content' => $input['content'],
            'attachments' => $input['attachments'] ?? [],
            'created_at' => date('Y-m-d H:i:s')
        ];
        $comments[] = $newComment;
        $storage->saveComments($comments);
        echo json_encode(['success' => true, 'comment' => $newComment]);
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'mass_assign') {
        if ($userData['role'] !== 'admin' && $userData['role'] !== 'Department Head') {
            http_response_code(403); exit;
        }
        $tasks = $storage->getTasks();
        foreach ($tasks as &$t) {
            if (in_array($t['id'], $input['ids'])) {
                $t['assigned_to'] = $input['executor_id'];
                $t['status'] = 'assigned';
            }
        }
        $storage->saveTasks($tasks);
        echo json_encode(['success' => true]);
        exit;
    }

    // Create task
    $tasks = $storage->getTasks();
    $createdAt = date('Y-m-d H:i:s');
    $deadline = $slaProvider->calculateDeadline($input['priority'], $createdAt);

    $newTask = [
        'id' => count($tasks) + 1,
        'title' => $input['title'],
        'description' => $input['description'],
        'category' => $input['category'],
        'priority' => $input['priority'],
        'status' => 'new',
        'creator_id' => $userData['id'],
        'creator_department' => $userData['department'],
        'assigned_to' => null,
        'completed_at' => null,
        'attachments' => $input['attachments'] ?? [],
        'custom_fields' => $input['custom_fields'] ?? [],
        'created_at' => $createdAt,
        'deadline' => $deadline
    ];
    $tasks[] = $newTask;
    $storage->saveTasks($tasks);

    $notifier->notify($userData['id'], "New Task Created", "Your task '{$newTask['title']}' has been created.");

    echo json_encode(['success' => true, 'task' => $newTask]);
} elseif ($method === 'PUT') {
    $tasks = $storage->getTasks();
    foreach ($tasks as &$t) {
        if ($t['id'] == $id) {
            if (isset($input['status'])) {
                $t['status'] = $input['status'];
                if ($input['status'] === 'done' || $input['status'] === 'rejected') {
                    $t['completed_at'] = date('Y-m-d H:i:s');
                }
            }
            if (isset($input['assigned_to'])) {
                $t['assigned_to'] = $input['assigned_to'];
                if ($t['status'] === 'new') $t['status'] = 'assigned';
            }
            // Update other fields if allowed...

            $storage->saveTasks($tasks);
            echo json_encode(['success' => true]);
            exit;
        }
    }
}
