<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

$user = Auth::authenticate();
Auth::checkRole($user, ['Administrator']);

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'users') {
        $users = Storage::read('users');
        foreach ($users as &$u) unset($u['password']);
        echo json_encode($users);
    } elseif ($action === 'settings') {
        $settings = Storage::read('settings');
        unset($settings['jwt_secret']);
        echo json_encode($settings);
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'save_user') {
        $users = Storage::read('users');
        if (!empty($data['id'])) {
            foreach ($users as &$u) {
                if ($u['id'] === $data['id']) {
                    $u['username'] = $data['username'];
                    $u['name'] = $data['name'];
                    $u['role'] = $data['role'];
                    $u['department'] = $data['department'] ?? 'General';
                    if (!empty($data['password'])) {
                        $u['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                    }
                    break;
                }
            }
        } else {
            $data['id'] = Storage::generateId();
            $data['password'] = password_hash($data['password'] ?? '123456', PASSWORD_DEFAULT);
            $users[] = $data;
        }
        Storage::write('users', $users);
        echo json_encode(['success' => true]);
    } elseif ($action === 'delete_user') {
        $users = Storage::read('users');
        $id = $data['id'] ?? '';
        $users = array_filter($users, fn($u) => $u['id'] !== $id);
        Storage::write('users', array_values($users));
        echo json_encode(['success' => true]);
    } elseif ($action === 'save_settings') {
        $settings = Storage::read('settings');
        foreach ($data as $key => $val) {
            if ($key !== 'jwt_secret') {
                $settings[$key] = $val;
            }
        }
        Storage::write('settings', $settings);
        echo json_encode(['success' => true]);
    } elseif ($action === 'init_mysql') {
        $store = new Storage();
        $store->setMode('mysql', $data['db']);
        echo json_encode(['success' => true]);
    }
}
