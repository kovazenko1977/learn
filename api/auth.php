<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/TokenProvider.php';
require_once __DIR__ . '/../includes/Logger.php';

$settings = json_decode(file_get_contents(__DIR__ . '/../data/settings.json'), true);
$storage = new Storage($settings);
$tokenProvider = new TokenProvider();
$logger = new Logger();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$input = json_decode(file_get_contents('php://input'), true);

if ($action === 'login' && $method === 'POST') {
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    $users = $storage->getUsers();
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            $logger->log($user['id'], "Login", "Successful login for user: " . $user['username']);
            $token = $tokenProvider->generateToken([
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'department' => $user['department']
            ]);
            echo json_encode(['success' => true, 'token' => $token, 'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role' => $user['role'],
                'department' => $user['department']
            ]]);
            exit;
        }
    }
    $logger->log(0, "Login Failed", "Failed login attempt for username: " . ($username ?? 'unknown'));
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    exit;
}

if ($action === 'recovery' && $method === 'POST') {
    $username = $input['username'] ?? '';
    $users = $storage->getUsers();
    $found = false;
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            $found = true;
            $token = bin2hex(random_bytes(16));
            $recoveryTokens = $storage->getRecoveryTokens();
            $recoveryTokens[] = [
                'id' => count($recoveryTokens) + 1,
                'token' => $token,
                'user_id' => $user['id'],
                'expires_at' => date('Y-m-d H:i:s', time() + 3600)
            ];
            $storage->saveRecoveryTokens($recoveryTokens);

            // In a real system, the token would be emailed.
            // For demo purposes, we will return the token in the message
            echo json_encode(['success' => true, 'message' => "Recovery token: $token (Demo only)"]);
            exit;
        }
    }
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

if ($action === 'reset_password' && $method === 'POST') {
    $token = $input['token'] ?? '';
    $newPassword = $input['password'] ?? '';

    $recoveryTokens = $storage->getRecoveryTokens();
    foreach ($recoveryTokens as $index => $rt) {
        if ($rt['token'] === $token && strtotime($rt['expires_at']) > time()) {
            $users = $storage->getUsers();
            foreach ($users as &$user) {
                if ($user['id'] === $rt['user_id']) {
                    $user['password'] = password_hash($newPassword, PASSWORD_BCRYPT);
                    $storage->saveUsers($users);

                    unset($recoveryTokens[$index]);
                    $storage->saveRecoveryTokens(array_values($recoveryTokens));

                    echo json_encode(['success' => true, 'message' => 'Password reset successful']);
                    exit;
                }
            }
        }
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit;
}

// Protected routes
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);
$userData = $tokenProvider->validateToken($token);

if (!$userData) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($action === 'profile' && $method === 'POST') {
    $users = $storage->getUsers();
    foreach ($users as &$user) {
        if ($user['id'] === $userData['id']) {
            if (!empty($input['full_name'])) $user['full_name'] = $input['full_name'];
            if (!empty($input['password'])) $user['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
            $storage->saveUsers($users);
            echo json_encode(['success' => true, 'message' => 'Profile updated']);
            exit;
        }
    }
}

if ($action === 'me' && $method === 'GET') {
    $users = $storage->getUsers();
    foreach ($users as $user) {
        if ($user['id'] === $userData['id']) {
            echo json_encode(['success' => true, 'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role' => $user['role'],
                'department' => $user['department']
            ]]);
            exit;
        }
    }
}
