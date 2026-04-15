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
    // Don't send hashes to frontend
    foreach ($users as &$user) unset($user['pin_hash']);
    // Sort by points for leaderboard
    usort($users, function($a, $b) { return ($b['points'] ?? 0) <=> ($a['points'] ?? 0); });
    echo json_encode($users);
} elseif ($method === 'POST') {
    Auth::requireAdmin();
    $data = Security::sanitize(json_decode(file_get_contents('php://input'), true));
    $users = Storage::read('users');

    if (isset($data['id']) && $data['id'] !== 'new') {
        // Update
        foreach ($users as &$user) {
            if ($user['id'] === $data['id']) {
                $user['name'] = $data['name'] ?? $user['name'];
                $user['role'] = $data['role'] ?? $user['role'];
                if (!empty($data['pin'])) {
                    $user['pin_hash'] = password_hash($data['pin'], PASSWORD_DEFAULT);
                }
                break;
            }
        }
    } else {
        // Create
        $newUser = [
            'id' => uniqid('u_'),
            'name' => $data['name'] ?? 'New Manager',
            'role' => $data['role'] ?? 'manager',
            'pin_hash' => password_hash($data['pin'] ?? '123456', PASSWORD_DEFAULT),
            'points' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $users[] = $newUser;
    }

    Storage::save('users', $users);
    echo json_encode(['success' => true]);
} elseif ($method === 'DELETE') {
    Auth::requireAdmin();
    $id = $_GET['id'] ?? '';
    if ($id === 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Cannot delete main admin']);
        exit;
    }
    $users = array_filter(Storage::read('users'), function($u) use ($id) { return $u['id'] !== $id; });
    Storage::save('users', array_values($users));
    echo json_encode(['success' => true]);
}
