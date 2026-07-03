<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/TokenProvider.php';

$tokenProvider = new TokenProvider();
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);
$userData = $tokenProvider->validateToken($token);

if (!$userData || $userData['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$settingsFile = __DIR__ . '/../data/settings.json';
$settings = json_decode(file_get_contents($settingsFile), true);
$storage = new Storage($settings);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'users') {
        echo json_encode($storage->getUsers());
        exit;
    }
    echo json_encode($settings);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'save_settings') {
        file_put_contents($settingsFile, json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'save_user') {
        $users = $storage->getUsers();
        if (isset($input['id'])) {
            foreach ($users as &$u) {
                if ($u['id'] == $input['id']) {
                    $u['username'] = $input['username'];
                    if (!empty($input['password'])) $u['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
                    $u['full_name'] = $input['full_name'];
                    $u['role'] = $input['role'];
                    $u['department'] = $input['department'];
                }
            }
        } else {
            $input['id'] = count($users) + 1;
            $input['password'] = password_hash($input['password'] ?: '123456', PASSWORD_BCRYPT);
            $users[] = $input;
        }
        $storage->saveUsers($users);
        echo json_encode(['success' => true]);
        exit;
    }
}
