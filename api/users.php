<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

$user = Auth::getUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$users = Storage::get('users');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        if ($user['role'] !== 'admin' && $user['role'] !== 'head') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
        foreach ($users as &$u) unset($u['password_hash']);
        echo json_encode($users);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'update_profile') {
        foreach ($users as &$u) {
            if ($u['id'] == $user['id']) {
                if (!empty($data['password'])) {
                    $u['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
                if (!empty($data['full_name'])) {
                    $u['full_name'] = $data['full_name'];
                }
                Storage::set('users', $users);
                echo json_encode(['success' => true]);
                exit;
            }
        }
    }

    if ($user['role'] === 'admin') {
        if ($action === 'create') {
            $newUser = [
                'id' => time(),
                'username' => $data['username'],
                'password_hash' => password_hash($data['password'] ?? '123456', PASSWORD_DEFAULT),
                'role' => $data['role'] ?? 'employee',
                'full_name' => $data['full_name'] ?? '',
                'department' => $data['department'] ?? ''
            ];
            $users[] = $newUser;
            Storage::set('users', $users);
            echo json_encode(['success' => true, 'user' => $newUser]);
        } elseif ($action === 'update') {
            $userId = $data['id'];
            foreach ($users as &$u) {
                if ($u['id'] == $userId) {
                    $u['role'] = $data['role'] ?? $u['role'];
                    $u['full_name'] = $data['full_name'] ?? $u['full_name'];
                    $u['department'] = $data['department'] ?? $u['department'];
                    if (!empty($data['password'])) {
                        $u['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
                    }
                    Storage::set('users', $users);
                    echo json_encode(['success' => true]);
                    exit;
                }
            }
        } elseif ($action === 'delete') {
            $userId = $data['id'];
            $users = array_filter($users, fn($u) => $u['id'] != $userId);
            Storage::set('users', array_values($users));
            echo json_encode(['success' => true]);
        }
    }
}
