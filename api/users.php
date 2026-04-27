<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';

$user = Auth::authenticate();
if (!$user || !Auth::isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$users = Storage::getData('users');

if ($method === 'GET') {
    foreach ($users as &$u) unset($u['password_hash']);
    echo json_encode($users);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!empty($data['password'])) {
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    unset($data['password']);

    if (empty($data['id'])) {
        $data['id'] = bin2hex(random_bytes(8));
        $data['created_at'] = date('Y-m-d H:i:s');
        $users[] = $data;
    } else {
        foreach ($users as &$u) {
            if ($u['id'] === $data['id']) {
                if (empty($data['password_hash'])) $data['password_hash'] = $u['password_hash'];
                $u = array_merge($u, $data);
                break;
            }
        }
    }
    Storage::saveData('users', $users);
    echo json_encode(['success' => true]);
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    $users = array_filter($users, fn($u) => $u['id'] !== $id);
    Storage::saveData('users', array_values($users));
    echo json_encode(['success' => true]);
}