<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

Auth::requireRole(['superadmin', 'admin_clients', 'client']);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $users = Storage::read('users');
        $currentUser = Auth::getCurrentUser();

        // Filter: Admin sees all, client sees only self
        if ($currentUser['role'] === 'client') {
            $users = array_filter($users, fn($u) => $u['id'] === $currentUser['id']);
        }

        foreach ($users as &$user) {
            unset($user['password']);
        }
        echo json_encode(['success' => true, 'users' => array_values($users)]);
        break;

    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        $raw_password = $data['password'] ?? '';
        $data = Security::sanitize($data);
        if (Storage::findOne('users', ['username' => $data['username']])) {
            echo json_encode(['success' => false, 'error' => 'Username already exists']);
            break;
        }
        $data['password'] = password_hash($raw_password, PASSWORD_BCRYPT);
        $data['status'] = $data['status'] ?? 'active';
        $data['created_at'] = date('Y-m-d H:i:s');
        // New fields
        $data['company_name'] = $data['company_name'] ?? '';
        $data['email'] = $data['email'] ?? '';
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
        $raw_password = $data['password'] ?? '';
        $data = Security::sanitize($data);
        $id = $_GET['id'] ?? $data['id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID required']);
            break;
        }

        $currentUser = Auth::getCurrentUser();
        if ($currentUser['role'] === 'client' && $id !== $currentUser['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
        unset($data['id']);
        if (!empty($raw_password)) {
            $data['password'] = password_hash($raw_password, PASSWORD_BCRYPT);
        } else {
            unset($data['password']);
        }
        Storage::update('users', $id, $data);
        Security::log('update_user', $_SESSION['user_id'], 'users', ['id' => $id]);
        echo json_encode(['success' => true]);
        break;

    case 'block':
        $id = $_GET['id'] ?? '';
        Storage::update('users', $id, ['status' => 'blocked']);
        Security::log('block_user', $_SESSION['user_id'], 'users', ['id' => $id]);
        echo json_encode(['success' => true]);
        break;

    case 'unblock':
        $id = $_GET['id'] ?? '';
        Storage::update('users', $id, ['status' => 'active']);
        Security::log('unblock_user', $_SESSION['user_id'], 'users', ['id' => $id]);
        echo json_encode(['success' => true]);
        break;

    case 'reset_password':
        $id = $_GET['id'] ?? '';
        $new_password = bin2hex(random_bytes(4)); // 8 chars random
        Storage::update('users', $id, ['password' => password_hash($new_password, PASSWORD_BCRYPT)]);
        Security::log('reset_password', $_SESSION['user_id'], 'users', ['id' => $id]);
        echo json_encode(['success' => true, 'new_password' => $new_password]);
        break;

    case 'delete':
        $id = $_GET['id'] ?? '';
        Storage::delete('users', $id);
        Security::log('delete_user', $_SESSION['user_id'], 'users', ['id' => $id]);
        echo json_encode(['success' => true]);
        break;

    case 'approve':
        $id = $_GET['id'] ?? '';
        Storage::update('users', $id, ['status' => 'active']);
        Security::log('approve_user', $_SESSION['user_id'], 'users', ['id' => $id]);
        echo json_encode(['success' => true]);
        break;

    case 'reject':
        $id = $_GET['id'] ?? '';
        Storage::update('users', $id, ['status' => 'rejected']);
        Security::log('reject_user', $_SESSION['user_id'], 'users', ['id' => $id]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
