<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $code = $data['code'] ?? null;
        echo json_encode(Auth::login($username, $password, $code));
        break;

    case 'logout':
        echo json_encode(Auth::logout());
        break;

    case 'check':
        if (Auth::check()) {
            $user = Auth::getCurrentUser();
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
