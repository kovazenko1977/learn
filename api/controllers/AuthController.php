<?php
/**
 * AuthController
 */

class AuthController {
    public function handle($method) {
        if ($method === 'GET') {
            $this->me();
        } else {
            json_out(['error' => 'Method not allowed'], 405);
        }
    }

    public function actionLogin() {
        $data = json_in();
        $phone = clean($data['phone'] ?? '');
        $password = clean($data['password'] ?? '');

        if (!$phone || !$password) {
            json_out(['error' => 'Заполните номер телефона и пароль'], 400);
        }

        $db = get_storage();
        $users = $db->get('users');
        $foundUser = null;

        foreach ($users as $u) {
            if ($u['phone'] === $phone) {
                $foundUser = $u;
                break;
            }
        }

        if (!$foundUser || !password_verify($password, $foundUser['password'])) {
            json_out(['error' => 'Неверный телефон или пароль'], 401);
        }

        if (isset($foundUser['is_blocked']) && $foundUser['is_blocked']) {
            json_out(['error' => 'Ваш аккаунт заблокирован'], 403);
        }

        $tokenPayload = [
            'id' => $foundUser['id'],
            'phone' => $foundUser['phone'],
            'role' => $foundUser['role']
        ];
        $token = jwt_encode($tokenPayload);

        unset($foundUser['password']);

        json_out([
            'success' => true,
            'token' => $token,
            'user' => $foundUser
        ]);
    }

    public function actionRegister() {
        $data = json_in();
        $name = clean($data['name'] ?? '');
        $phone = clean($data['phone'] ?? '');
        $password = clean($data['password'] ?? '');

        if (!$name || !$phone || mb_strlen($password) < 4) {
            json_out(['error' => 'Заполните имя, телефон и пароль (мин. 4 символа)'], 400);
        }

        $db = get_storage();
        $users = $db->get('users');

        foreach ($users as $u) {
            if ($u['phone'] === $phone) {
                json_out(['error' => 'Пользователь с таким телефоном уже зарегистрирован'], 400);
            }
        }

        $newUser = [
            'name' => $name,
            'phone' => $phone,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => 'user',
            'is_blocked' => false,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $id = $db->insert('users', $newUser);
        $newUser['id'] = $id;

        $tokenPayload = [
            'id' => $id,
            'phone' => $newUser['phone'],
            'role' => $newUser['role']
        ];
        $token = jwt_encode($tokenPayload);

        unset($newUser['password']);

        json_out([
            'success' => true,
            'token' => $token,
            'user' => $newUser
        ]);
    }

    public function actionMe() {
        $currentUser = require_auth();
        $db = get_storage();
        $user = $db->find('users', $currentUser['id']);
        if (!$user) {
            json_out(['error' => 'Пользователь не найден'], 404);
        }
        unset($user['password']);
        json_out(['user' => $user]);
    }

    public function me() {
        $this->actionMe();
    }
}
