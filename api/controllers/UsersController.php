<?php
/**
 * UsersController
 */

class UsersController {
    public function handle($method) {
        require_auth(true);

        if ($method === 'GET') {
            $this->getAll();
        } elseif ($method === 'POST') {
            $this->create();
        } elseif ($method === 'PUT') {
            $this->update();
        } elseif ($method === 'DELETE') {
            $this->delete();
        } else {
            json_out(['error' => 'Method not allowed'], 405);
        }
    }

    public function actionCreate() {
        $this->create();
    }

    private function getAll() {
        $db = get_storage();
        $users = $db->get('users');

        foreach ($users as &$u) {
            unset($u['password']);
        }

        json_out(['users' => $users]);
    }

    private function create() {
        $data = json_in();
        $name = clean($data['name'] ?? '');
        $phone = clean($data['phone'] ?? '');
        $password = clean($data['password'] ?? '1111');
        $role = clean($data['role'] ?? 'user');

        if (!$name || !$phone) {
            json_out(['error' => 'Заполните имя и телефон'], 400);
        }

        $db = get_storage();
        $users = $db->get('users');

        foreach ($users as $u) {
            if ($u['phone'] === $phone) {
                json_out(['error' => 'Пользователь с таким телефоном уже существует'], 400);
            }
        }

        $newUser = [
            'name' => $name,
            'phone' => $phone,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => in_array($role, ['admin', 'user']) ? $role : 'user',
            'is_blocked' => false,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $id = $db->insert('users', $newUser);
        $newUser['id'] = $id;
        unset($newUser['password']);

        json_out(['success' => true, 'user' => $newUser]);
    }

    private function update() {
        $data = json_in();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : ($data['id'] ?? null);

        if (!$id) {
            json_out(['error' => 'ID пользователя не указан'], 400);
        }

        $db = get_storage();
        $user = $db->find('users', $id);

        if (!$user) {
            json_out(['error' => 'Пользователь не найден'], 404);
        }

        $updateData = [
            'name' => clean($data['name'] ?? $user['name']),
            'phone' => clean($data['phone'] ?? $user['phone']),
            'role' => clean($data['role'] ?? $user['role']),
            'is_blocked' => isset($data['is_blocked']) ? (bool)$data['is_blocked'] : ($user['is_blocked'] ?? false)
        ];

        if (!empty($data['password']) && mb_strlen($data['password']) >= 4) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $db->update('users', $id, $updateData);
        json_out(['success' => true]);
    }

    private function delete() {
        $currentUser = require_auth(true);
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$id) {
            json_out(['error' => 'ID пользователя не указан'], 400);
        }

        if ($id === (int)$currentUser['id']) {
            json_out(['error' => 'Нельзя удалить собственного пользователя'], 400);
        }

        $db = get_storage();
        $user = $db->find('users', $id);

        if ($user && ($user['role'] ?? '') === 'admin') {
            json_out(['error' => 'Нельзя удалить другого администратора'], 400);
        }

        $db->delete('users', $id);
        json_out(['success' => true]);
    }
}
