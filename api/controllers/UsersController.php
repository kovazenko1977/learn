<?php
// api/controllers/UsersController.php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../storage.php';

class UsersController {
    private $storage;

    public function __construct() {
        $this->storage = Storage::getInstance();
    }

    public function index() {
        require_admin();
        $users = $this->storage->all('users');
        $sanitized = array_map(function($u) {
            unset($u['password']);
            return $u;
        }, $users);
        json_out($sanitized);
    }

    public function updateRole($id = null) {
        require_admin();
        if (!$id) {
            json_out(['error' => 'ID пользователя не указан'], 400);
        }

        $data = json_in();
        $role = trim($data['role'] ?? 'user');

        if (!in_array($role, ['user', 'admin'])) {
            json_out(['error' => 'Недопустимая роль'], 400);
        }

        $updated = $this->storage->update('users', $id, ['role' => $role]);
        unset($updated['password']);
        json_out($updated);
    }

    public function toggleBlock($id = null) {
        require_admin();
        if (!$id) {
            json_out(['error' => 'ID пользователя не указан'], 400);
        }

        $user = $this->storage->getById('users', $id);
        if (!$user) {
            json_out(['error' => 'Пользователь не найден'], 404);
        }

        $newStatus = empty($user['blocked']) ? 1 : 0;
        $updated = $this->storage->update('users', $id, ['blocked' => $newStatus]);
        unset($updated['password']);
        json_out($updated);
    }

    public function resetPassword($id = null) {
        require_admin();
        if (!$id) {
            json_out(['error' => 'ID пользователя не указан'], 400);
        }

        $data = json_in();
        $newPassword = trim($data['password'] ?? '1111');

        if (strlen($newPassword) < 4) {
            json_out(['error' => 'Пароль должен быть не короче 4 символов'], 400);
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->storage->update('users', $id, ['password' => $hash]);

        json_out(['success' => true, 'message' => 'Пароль пользователя успешно сброшен']);
    }
}
