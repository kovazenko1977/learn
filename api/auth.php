<?php
require_once '../includes/Auth.php';

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
