<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

Auth::requireRole(['superadmin', 'admin_clients']);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $users = Storage::read('users');
        foreach ($users as &$user) {
            unset($user['password']);
        }
        echo json_encode(['success' => true, 'users' => array_values($users)]);
        break;

    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Security::sanitize($data);
        if (Storage::findOne('users', ['username' => $data['username']])) {
            echo json_encode(['success' => false, 'error' => 'Username already exists']);
            break;
        }
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $data['status'] = $data['status'] ?? 'active';
        $data['created_at'] = date('Y-m-d H:i:s');
        // New fields
        $data['company_name'] = $data['company_name'] ?? '';
        $data['tax_id'] = $data['tax_id'] ?? '';
        $data['address'] = $data['address'] ?? '';
        $data['contact_person'] = $data['contact_person'] ?? '';
        $data['phone'] = $data['phone'] ?? '';

        $id = Storage::insert('users', $data);
        Security::log('create_user', $_SESSION['user_id'], 'users', ['id' => $id, 'username' => $data['username']]);
        echo json_encode(['success' => true, 'id' => $id]);
        break;

    case 'update':
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Security::sanitize($data);
        $id = $data['id'];
        unset($data['id']);
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        } else {
            unset($data['password']);
        }
        Storage::update('users', $id, $data);
        Security::log('update_user', $_SESSION['user_id'], 'users', ['id' => $id]);
        echo json_encode(['success' => true]);
        break;

    case 'delete':
        $id = $_GET['id'] ?? '';
        Storage::delete('users', $id);
        Security::log('delete_user', $_SESSION['user_id'], 'users', ['id' => $id]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
