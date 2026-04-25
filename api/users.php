<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';

$user = Auth::authenticate();
Auth::checkRole($user, ['Administrator']);

$storage = Storage::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $users = $storage->read('users');
    foreach ($users as &$u) unset($u['password_hash']);
    echo json_encode($users);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $storage->atomicUpdate('users', function($users) use ($data) {
        if (isset($data['id'])) {
            // Update
            foreach ($users as &$u) {
                if ($u['id'] == $data['id']) {
                    $u['username'] = $data['username'];
                    $u['role'] = $data['role'];
                    $u['full_name'] = $data['full_name'];
                    if (!empty($data['password'])) {
                        $u['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
                    }
                    break;
                }
            }
        } else {
            // Create
            $data['id'] = time();
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            unset($data['password']);
            $users[] = $data;
        }
        return $users;
    });
    echo json_encode(['success' => true]);
}
