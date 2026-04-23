<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';

Auth::requireAuth();
$currentUser = Auth::getUser();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $users = Storage::read('users');
    // Hide PINs for safety
    foreach ($users as &$u) {
        unset($u['pin_hash']);
        // Check "Last Active" - if within 5 mins
        $u['online'] = false;
        if (isset($u['last_seen'])) {
            $u['online'] = (time() - strtotime($u['last_seen'])) < 300;
        }
    }
    echo json_encode($users);
} elseif ($method === 'POST') {
    Auth::requireAdmin();
    $data = Security::sanitize(json_decode(file_get_contents('php://input'), true));
    $users = Storage::read('users');

    if (isset($data['id']) && $data['id'] !== 'new') {
        foreach ($users as &$u) {
            if ($u['id'] === $data['id']) {
                $u['name'] = $data['name'];
                $u['role'] = $data['role'];
                if (!empty($data['pin'])) {
                    $u['pin_hash'] = password_hash($data['pin'], PASSWORD_DEFAULT);
                }
                break;
            }
        }
    } else {
        $users[] = [
            'id' => uniqid('u_'),
            'name' => $data['name'],
            'role' => $data['role'],
            'pin_hash' => password_hash($data['pin'], PASSWORD_DEFAULT),
            'points' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    Storage::save('users', $users);
    echo json_encode(['success' => true]);
} elseif ($method === 'DELETE') {
    Auth::requireAdmin();
    $id = $_GET['id'] ?? '';
    if ($id === 'admin') exit;
    $users = Storage::read('users');
    $users = array_filter($users, function($u) use ($id) { return $u['id'] !== $id; });
    Storage::save('users', array_values($users));
    echo json_encode(['success' => true]);
}
