<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$settingsFile = __DIR__ . '/../data/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];

$storage = new Storage($settings);
$auth = new Auth($storage, $settings);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? 'login';

    if ($action === 'login') {
        $result = $auth->login($input['username'] ?? '', $input['password'] ?? '');
        if ($result) {
            echo json_encode($result);
        } else {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['error' => 'Неверный логин или пароль']);
        }
    } elseif ($action === 'logout') {
        $auth->logout();
        echo json_encode(['success' => true]);
    } elseif ($action === 'register') {
        $auth->requireRole('admin');
        $input['password_hash'] = password_hash($input['password'], PASSWORD_BCRYPT);
        unset($input['password']);
        $id = $storage->save('users', $input);
        echo json_encode(['id' => $id, 'success' => true]);
    } elseif ($action === 'update_profile') {
        $auth->requireAuth();
        $user = $auth->getUser();

        $dbUser = $storage->find('users', $user['id']);
        if (isset($input['new_password']) && $input['new_password']) {
            if (!password_verify($input['current_password'], $dbUser['password_hash'])) {
                header('HTTP/1.1 400 Bad Request');
                echo json_encode(['error' => 'Текущий пароль неверен']);
                exit;
            }
            $dbUser['password_hash'] = password_hash($input['new_password'], PASSWORD_BCRYPT);
        }

        if (isset($input['full_name'])) $dbUser['full_name'] = $input['full_name'];
        if (isset($input['phone'])) $dbUser['phone'] = $input['phone'];

        $storage->save('users', $dbUser);
        echo json_encode(['success' => true]);
    } elseif ($action === 'forgot_password') {
        $username = $input['username'] ?? '';
        $users = $storage->get('users', ['username' => $username]);
        if (!empty($users)) {
            $user = array_values($users)[0];
            $token = bin2hex(random_bytes(16));
            $recovery = [
                'token' => $token,
                'user_id' => $user['id'],
                'expires' => date('Y-m-d H:i:s', time() + 3600)
            ];
            $storage->atomicWrite('recoveries', array_merge($storage->get('recoveries'), [$recovery]));
            echo json_encode(['success' => true, 'message' => 'Инструкции по восстановлению отправлены на вашу почту']);
        } else {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Пользователь не найден']);
        }
    } elseif ($action === 'reset_password') {
        $token = $input['token'] ?? '';
        $newPassword = $input['password'] ?? '';
        $recoveries = $storage->get('recoveries');
        $found = null;
        foreach ($recoveries as $index => $r) {
            if ($r['token'] === $token && strtotime($r['expires']) > time()) {
                $found = $r;
                unset($recoveries[$index]);
                break;
            }
        }
        if ($found) {
            $user = $storage->find('users', $found['user_id']);
            if ($user) {
                $user['password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT);
                $storage->save('users', $user);
                $storage->atomicWrite('recoveries', array_values($recoveries));
                echo json_encode(['success' => true]);
            } else {
                header('HTTP/1.1 404 Not Found');
                echo json_encode(['error' => 'User not found']);
            }
        } else {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Invalid or expired token']);
        }
    }
} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? 'me';
    if ($action === 'me') {
        $user = $auth->getUser();
        if ($user) {
            echo json_encode($user);
        } else {
            echo json_encode(null);
        }
    } elseif ($action === 'users') {
        $auth->requireRole(['admin', 'head']);
        $users = $storage->get('users');
        foreach ($users as &$u) unset($u['password_hash']);
        echo json_encode($users);
    }
}
