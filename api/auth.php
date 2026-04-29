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

if ($action == 'login' && $method == 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    // Fallback to $_POST if JSON is empty/invalid
    if (empty($data) && !empty($_POST)) {
        $data = $_POST;
    }

    if (empty($data['login']) || empty($data['password'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'Please enter login and password']));
    }

    $login = trim((string)$data['login']);
    $password = (string)$data['password'];

    // Manual search in users to be as resilient as possible
    $users = $storage->readCollection('users');
    $foundUser = null;
    foreach ($users as $u) {
        if (isset($u['login']) && strcasecmp(trim((string)$u['login']), $login) === 0) {
            $foundUser = $u;
            break;
        }
    }

    if (!$foundUser) {
        http_response_code(401);
        exit(json_encode(['message' => 'User not found']));
    }

    // Check is_active (handle 1, "1", true, etc)
    $isActive = isset($foundUser['is_active']) ? (int)$foundUser['is_active'] : 0;
    if ($isActive !== 1) {
        http_response_code(401);
        exit(json_encode(['message' => 'Account is inactive']));
    }

    if (password_verify($password, $foundUser['password_hash'])) {
        unset($foundUser['password_hash']);

        $payload = [
            'id' => $foundUser['id'],
            'role' => $foundUser['role'],
            'department_id' => $foundUser['department_id'] ?? null,
            'exp' => time() + 86400
        ];

        $jsonPayload = json_encode($payload);
        $secret = "php-crm-hop-very-secret-key-12345";
        $signature = hash_hmac('sha256', $jsonPayload, $secret);
        $token = base64_encode($jsonPayload) . '.' . $signature;

        echo json_encode(['token' => $token, 'user' => $foundUser]);
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Incorrect password']);
    }
} elseif ($action == 'me') {
    $user = Auth::check();
    if ($user) {
        $userData = $storage->findOne('users', ['id' => $user['id']]);
        if ($userData) {
            unset($userData['password_hash']);
            echo json_encode($userData);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'User session valid but data missing']);
        }
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Unauthorized']);
    }
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Action not found']);
}
