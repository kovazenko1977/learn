<?php
// api/controllers/ProfileController.php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../storage.php';

class ProfileController {
    private $storage;

    public function __construct() {
        $this->storage = Storage::getInstance();
    }

    public function index() {
        $authUser = require_auth();
        $user = $this->storage->getById('users', $authUser['id']);
        if (!$user) {
            json_out(['error' => 'Профиль не найден'], 404);
        }

        json_out([
            'id' => $user['id'],
            'name' => $user['name'],
            'phone' => $user['phone'],
            'role' => $user['role'] ?? 'user',
            'created_at' => $user['created_at'] ?? null
        ]);
    }

    public function update() {
        $authUser = require_auth();
        $data = json_in();

        $updateData = [];
        if (!empty($data['name'])) {
            $updateData['name'] = trim($data['name']);
        }
        if (!empty($data['password'])) {
            if (strlen(trim($data['password'])) < 4) {
                json_out(['error' => 'Пароль должен содержать не менее 4 символов'], 400);
            }
            $updateData['password'] = password_hash(trim($data['password']), PASSWORD_BCRYPT);
        }

        if (empty($updateData)) {
            json_out(['error' => 'Нет данных для обновления'], 400);
        }

        $updated = $this->storage->update('users', $authUser['id'], $updateData);

        json_out([
            'success' => true,
            'user' => [
                'id' => $updated['id'],
                'name' => $updated['name'],
                'phone' => $updated['phone'],
                'role' => $updated['role'] ?? 'user'
            ]
        ]);
    }
}
