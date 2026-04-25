<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$settingsFile = __DIR__ . '/../data/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];

$storage = new Storage($settings);
$auth = new Auth($storage, $settings);

$auth->requireRole('admin');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $users = $storage->get('users');
    foreach ($users as &$user) unset($user['password_hash']);
    echo json_encode($users);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['password'])) {
        $input['password_hash'] = password_hash($input['password'], PASSWORD_DEFAULT);
        unset($input['password']);
    }

    $id = $storage->save('users', $input);
    echo json_encode(['id' => $id, 'success' => true]);
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $storage->delete('users', $id);
        echo json_encode(['success' => true]);
    }
}
