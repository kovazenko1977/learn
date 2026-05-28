<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$settingsFile = __DIR__ . '/../data/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];

$storage = new Storage($settings);
$auth = new Auth($storage, $settings);

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'login') {
        $result = $auth->login($input['username'] ?? '', $input['password'] ?? '');
        if ($result) {
            echo json_encode($result);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    } elseif ($action === 'logout') {
        $auth->logout();
        echo json_encode(['success' => true]);
    } elseif ($action === 'change_password') {
        $auth->requireAuth();
        $user = $auth->getUser();

        $dbUser = $storage->find('users', $user['id']);
        if ($dbUser && password_verify($input['currentPassword'], $dbUser['password_hash'])) {
            $dbUser['password_hash'] = password_hash($input['newPassword'], PASSWORD_DEFAULT);
            $storage->save('users', $dbUser);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Current password incorrect']);
        }
    } elseif ($action === 'reset_password') {
        $identity = $input['identity'] ?? '';
        $users = $storage->get('users');
        $found = false;
        foreach ($users as $u) {
            if ($u['username'] === $identity) {
                $found = true;
                // In a real app, send email with token
                break;
            }
        }
        if ($found) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'me') {
        $user = $auth->getUser();
        if ($user) {
            echo json_encode($user);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
        }
    }
}
