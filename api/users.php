<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/TokenProvider.php';

$storage = new Storage();
$currentUser = TokenProvider::getCurrentUser();

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Banned check
$currentUserFull = $storage->getById('users', $currentUser['id']);
if ($currentUserFull && !empty($currentUserFull['banned'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'User is banned', 'banned' => true]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

// Allow ping/heartbeat
if ($method === 'POST' && isset($_GET['ping'])) {
    if ($currentUserFull) {
        $currentUserFull['last_seen'] = time();
        $storage->save('users', $currentUserFull);
        echo json_encode(['success' => true, 'last_seen' => $currentUserFull['last_seen']]);
        exit;
    }
}

// Restrict modify actions to Administrator only
if (($method === 'POST' || $method === 'DELETE') && $currentUser['role'] !== 'Administrator') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

if ($method === 'GET') {
    if ($id) {
        $u = $storage->getById('users', $id);
        if ($u) {
            unset($u['password']);
        }
        echo json_encode($u);
    } else {
        $users = $storage->getAll('users');
        foreach ($users as &$u) {
            unset($u['password']);
        }
        echo json_encode($users);
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!empty($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
    } elseif ($id) {
        $existing = $storage->getById('users', $id);
        $data['password'] = $existing['password'];
    }
    // Set default fields if missing
    if (!isset($data['last_seen'])) $data['last_seen'] = 0;
    if (!isset($data['banned'])) $data['banned'] = 0;

    $userId = $storage->save('users', $data);
    echo json_encode(['success' => true, 'id' => $userId]);
} elseif ($method === 'DELETE') {
    if ($id) {
        $storage->delete('users', $id);
        echo json_encode(['success' => true]);
    }
}
