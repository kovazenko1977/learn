<?php
require_once __DIR__ . '/../includes/Auth.php';

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $passcode = $_POST['passcode'] ?? '';
    $auth = new Auth();
    if ($auth->login($passcode)) {
        echo json_encode(['success' => true, 'user' => Auth::user()]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid 6-digit passcode']);
    }
    exit;
}

if ($action === 'check') {
    echo json_encode(['logged_in' => Auth::check(), 'user' => Auth::user()]);
    exit;
}

if ($action === 'logout') {
    $auth = new Auth();
    $auth->logout();
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'users') {
    if (!Auth::check()) exit;
    $storage = new Storage('users.json');
    echo json_encode($storage->read());
    exit;
}

if ($action === 'update_profile') {
    if (!Auth::check()) exit;
    $username = $_POST['username'] ?? '';
    if (empty($username)) exit;

    $user = Auth::user();
    $storage = new Storage('users.json');
    $users = $storage->read();

    foreach ($users as &$u) {
        if ($u['id'] === $user['id']) {
            $u['username'] = $username;
            $_SESSION['user'] = $u;
            break;
        }
    }
    $storage->write($users);
    echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
    exit;
}

if ($action === 'add_user') {
    if (!Auth::check()) exit;
    $username = $_POST['username'] ?? '';
    $passcode = $_POST['passcode'] ?? '';

    if (empty($username) || !preg_match('/^\d{6}$/', $passcode)) {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
        exit;
    }

    $storage = new Storage('users.json');
    $users = $storage->read();

    foreach ($users as $u) {
        if ($u['passcode'] === $passcode) {
            echo json_encode(['success' => false, 'error' => 'Код уже используется']);
            exit;
        }
    }

    $users[] = [
        'id' => uniqid(),
        'passcode' => $passcode,
        'username' => $username,
        'avatar' => 'assets/img/default-avatar.png',
        'role' => 'member'
    ];
    $storage->write($users);
    echo json_encode(['success' => true]);
    exit;
}
