<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'login') {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $token = Auth::login($username, $password);
        if ($token) {
            echo json_encode(['token' => $token]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    } elseif ($action === 'recover') {
        // Mocking password recovery. In a real app, this would send an email.
        $username = $data['username'] ?? '';
        $users = Storage::read('users');
        $found = false;
        foreach ($users as $u) {
            if ($u['username'] === $username) {
                $found = true;
                break;
            }
        }
        if ($found) {
            echo json_encode(['success' => true, 'message' => 'Instructions sent to ' . $username]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
    }
}
