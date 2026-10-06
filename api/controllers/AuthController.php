<?php
// api/controllers/AuthController.php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../storage.php';

class AuthController {
    private $storage;

    public function __construct() {
        $this->storage = Storage::getInstance();
    }

    public function login() {
        $data = json_in();
        $phone = trim($data['phone'] ?? '');
        $password = trim($data['password'] ?? '');

        if (empty($phone) || empty($password)) {
            json_out(['error' => 'Заполните телефон и пароль'], 400);
        }

        // Check if preseeded admin requested or match existing users
        $users = $this->storage->get('users', ['phone' => $phone]);
        $user = !empty($users) ? $users[0] : null;

        if (!$user && $phone === '1111' && $password === '1111') {
            // Auto create or fetch seed admin
            $user = [
                'id' => 'user_admin_seed',
                'name' => 'Администратор',
                'phone' => '1111',
                'password' => password_hash('1111', PASSWORD_BCRYPT),
                'role' => 'admin',
                'blocked' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $this->storage->insert('users', $user);
        }

        if (!$user) {
            json_out(['error' => 'Пользователь с таким телефоном не найден'], 400);
        }

        if (!empty($user['blocked'])) {
            json_out(['error' => 'Ваш аккаунт заблокирован администратором'], 403);
        }

        if (!password_verify($password, $user['password'])) {
            json_out(['error' => 'Неверный пароль'], 400);
        }

        $payload = [
            'id' => $user['id'],
            'name' => $user['name'],
            'phone' => $user['phone'],
            'role' => $user['role'] ?? 'user'
        ];

        $token = jwt_encode($payload);

        json_out([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'phone' => $user['phone'],
                'role' => $user['role'] ?? 'user'
            ]
        ]);
    }

    public function register() {
        $data = json_in();
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $password = trim($data['password'] ?? '');

        if (empty($name) || empty($phone) || empty($password)) {
            json_out(['error' => 'Все поля (имя, телефон, пароль) обязательны'], 400);
        }

        if (strlen($password) < 4) {
            json_out(['error' => 'Пароль должен содержать минимум 4 символа'], 400);
        }

        $existing = $this->storage->get('users', ['phone' => $phone]);
        if (!empty($existing)) {
            json_out(['error' => 'Пользователь с таким телефоном уже зарегистрирован'], 400);
        }

        $role = ($phone === '1111') ? 'admin' : 'user';

        $newUser = [
            'id' => 'user_' . uniqid(),
            'name' => $name,
            'phone' => $phone,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role,
            'blocked' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->storage->insert('users', $newUser);

        $payload = [
            'id' => $newUser['id'],
            'name' => $newUser['name'],
            'phone' => $newUser['phone'],
            'role' => $newUser['role']
        ];

        $token = jwt_encode($payload);

        json_out([
            'token' => $token,
            'user' => [
                'id' => $newUser['id'],
                'name' => $newUser['name'],
                'phone' => $newUser['phone'],
                'role' => $newUser['role']
            ]
        ]);
    }

    public function me() {
        $authUser = require_auth();
        $user = $this->storage->getById('users', $authUser['id']);
        if (!$user) {
            json_out(['error' => 'Пользователь не найден'], 404);
        }
        json_out([
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'phone' => $user['phone'],
                'role' => $user['role'] ?? 'user',
                'blocked' => $user['blocked'] ?? 0
            ]
        ]);
    }
}
