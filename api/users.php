<?php
Auth::requireRole(['admin']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        $u = Storage::read('users', $id);
        if ($u) unset($u['password_hash']);
        echo json_encode($u);
    } else {
        $users = Storage::list('users');
        foreach ($users as &$u) unset($u['password_hash']);
        echo json_encode($users);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id']) ? $input['id'] : uniqid();

    $existing = Storage::read('users', $id);
    if ($existing) {
        Storage::saveVersion('users', $id, $existing);
        $input['password_hash'] = $existing['password_hash'];
    }

    if (!empty($input['password'])) {
        $input['password_hash'] = Security::hashPassword($input['password']);
    }
    unset($input['password']);

    $input['id'] = $id;
    Storage::write('users', $id, Security::sanitize($input));
    echo json_encode(['success' => true, 'id' => $id]);
} elseif ($method === 'DELETE') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id === $_SESSION['user_id']) {
        echo json_encode(['error' => 'Cannot delete yourself']);
        exit;
    }
    Storage::delete('users', $id);
    echo json_encode(['success' => true]);
}
