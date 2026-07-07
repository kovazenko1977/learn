<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/TokenProvider.php';
require_once __DIR__ . '/../includes/Logger.php';

$tokenProvider = new TokenProvider();
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);
$userData = $tokenProvider->validateToken($token);

if (!$userData) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$settingsFile = __DIR__ . '/../data/settings.json';
$settings = json_decode(file_get_contents($settingsFile), true);
$storage = new Storage($settings);
$logger = new Logger();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'users') {
        $users = $storage->getUsers();
        if ($userData['role'] !== 'admin') {
            // Non-admins only get public info
            $users = array_map(function($u) {
                return ['id' => $u['id'], 'full_name' => $u['full_name'], 'role' => $u['role']];
            }, $users);
        }
        echo json_encode($users);
        exit;
    }
    echo json_encode($settings);
} elseif ($method === 'POST') {
    if ($userData['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'save_settings') {
        file_put_contents($settingsFile, json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $logger->log($userData['id'], "Settings Updated", "System configuration changed.");
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
