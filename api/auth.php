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

    if (empty($data) && !empty($_POST)) {
        $data = $_POST;
    }

    $login = isset($data['login']) ? trim((string)$data['login']) : '';
    $password = isset($data['password']) ? (string)$data['password'] : '';

    if (empty($login) || empty($password)) {
        http_response_code(400);
        exit(json_encode(['message' => 'Both login and password are required']));
    }

    // Attempt login
    $result = Auth::login($login, $password, $storage);

    if ($result) {
        echo json_encode($result);
    } else {
        http_response_code(401);

        // Debugging for 'admin' user specifically to help the user
        if ($login === 'admin') {
             $users = $storage->readCollection('users');
             $found = false;
             foreach($users as $u) if(isset($u['login']) && $u['login'] === 'admin') $found = true;

             if (!$found) {
                 exit(json_encode(['message' => 'Admin user missing from database. Run system_fix.php']));
             }
        }

        echo json_encode(['message' => 'Invalid login or password']);
    }
} elseif ($action == 'me') {
    $user = Auth::check();
    if ($user) {
        $userData = $storage->findOne('users', ['id' => $user['id']]);
        if (!$userData) {
            $users = $storage->readCollection('users');
            foreach ($users as $u) if ($u['id'] == $user['id']) { $userData = $u; break; }
        }

        if ($userData) {
            unset($userData['password_hash']);
            echo json_encode($userData);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Profile not found']);
        }
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Unauthorized']);
    }
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Unknown action']);
}
