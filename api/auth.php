<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$storage = new Storage(__DIR__ . '/../data');
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Check global auth requirement
$settings = $storage->findOne('settings', ['id' => 'global']) ?: ['auth_enabled' => false, 'announcement' => ''];
$authRequired = isset($settings['auth_enabled']) ? (bool)$settings['auth_enabled'] : false;

if ($action == 'config') {
    echo json_encode([
        'auth_required' => $authRequired,
        'announcement' => $settings['announcement'] ?? '',
        'notify_sound' => isset($settings['notify_sound']) ? (bool)$settings['notify_sound'] : false,
        'notify_browser' => isset($settings['notify_browser']) ? (bool)$settings['notify_browser'] : false,
        'notify_new_text' => $settings['notify_new_text'] ?? ''
    ]);
    exit;
}

if ($action == 'login' && $method == 'POST') {
    if (!$authRequired) {
        // Auto-login as admin if auth is disabled
        $admin = $storage->findOne('users', ['role' => 'admin']);
        if (!$admin) {
            // Emergency fallback if admin missing
            $admin = ['id' => 1, 'login' => 'admin', 'role' => 'admin', 'full_name' => 'System Admin'];
        }
        $result = Auth::forceLogin($admin);
        echo json_encode($result);
        exit;
    }

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (empty($data) && !empty($_POST)) $data = $_POST;

    $login = $data['login'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($login) || empty($password)) {
        http_response_code(400);
        exit(json_encode(['message' => 'Credentials required']));
    }

    $result = Auth::login($login, $password, $storage);
    if ($result) {
        // Log the login event
        $storage->insert('login_logs', [
            'user_id' => $result['user']['id'],
            'full_name' => $result['user']['full_name'],
            'login' => $result['user']['login'],
            'timestamp' => date('c'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        echo json_encode($result);
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Invalid login or password']);
    }
} elseif ($action == 'me') {
    if (!$authRequired) {
        $admin = $storage->findOne('users', ['role' => 'admin']) ?: ['id' => 1, 'role' => 'admin', 'full_name' => 'System Admin'];
        unset($admin['password_hash']);
        echo json_encode($admin);
        exit;
    }

    $user = Auth::check();
    if ($user) {
        $userData = $storage->findOne('users', ['id' => $user['id']]);
        if ($userData) {
            unset($userData['password_hash']);
            echo json_encode($userData);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'User not found']);
        }
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Unauthorized']);
    }
}
