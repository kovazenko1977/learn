<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        echo json_encode(Auth::login($username, $password));
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

    case 'register':
        $data = json_decode(file_get_contents('php://input'), true);
        $raw_password = $data['password'] ?? '';
        $data = Security::sanitize($data);

        if (empty($data['username']) || empty($raw_password)) {
            echo json_encode(['success' => false, 'error' => 'Логин и пароль обязательны']);
            break;
        }

        if (Storage::findOne('users', ['username' => $data['username']])) {
            echo json_encode(['success' => false, 'error' => 'Такой логин уже занят']);
            break;
        }

        $newUser = [
            'username' => $data['username'],
            'password' => password_hash($raw_password, PASSWORD_BCRYPT),
            'company_name' => $data['company_name'] ?? '',
            'tax_id' => $data['tax_id'] ?? '',
            'email' => $data['email'] ?? '',
            'role' => 'client',
            'status' => 'Ожидает',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $id = Storage::insert('users', $newUser);

        require_once __DIR__ . '/../includes/Mailer.php';
        Mailer::notifyNewRegistration($data['username'], $data['company_name'] ?? 'Не указана');

        Security::log('registration_request', $id, 'users');
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
