<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

header('Content-Type: application/json');

$storage = new Storage();
$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $login = $data['login'] ?? '';
    $password = $data['password'] ?? '';

    $users = $storage->getAll('users');
    $user = null;
    foreach ($users as $u) {
        if ($u['login'] === $login) {
            $user = $u;
            break;
        }
    }

    if ($user && password_verify($password, $user['password'])) {
        $token = Auth::generateToken([
            'id' => $user['id'],
            'login' => $user['login'],
            'role' => $user['role'],
            'name' => $user['name'],
            'department_id' => $user['department_id']
        ]);
        echo json_encode(['token' => $token, 'user' => [
            'id' => $user['id'],
            'login' => $user['login'],
            'role' => $user['role'],
            'name' => $user['name']
        ]]);
    } else {
        header('HTTP/1.0 401 Unauthorized');
        echo json_encode(['error' => 'Неверный логин или пароль']);
    }
} elseif ($action === 'me') {
    $user = Auth::checkRole(['admin', 'head', 'executor', 'employee']);
    echo json_encode($user);
} elseif ($action === 'profile') {
    $currentUser = Auth::checkRole(['admin', 'head', 'executor', 'employee']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $user = $storage->getById('users', $currentUser['id']);

        if (isset($data['name'])) $user['name'] = $data['name'];
        if (isset($data['email'])) $user['email'] = $data['email'];
        if (isset($data['password']) && !empty($data['password'])) {
            $user['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $storage->save('users', $user);
        echo json_encode(['success' => true]);
    }
} else {
    header('HTTP/1.0 404 Not Found');
    echo json_encode(['error' => 'Action not found']);
}
