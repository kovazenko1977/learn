<?php
require_once 'Storage.php';

class AuthManager {
    private $storage;
    private $filename = 'users';

    public function __construct() {
        $this->storage = new Storage();
    }

    public function seedAdmin() {
        $users = $this->storage->read($this->filename);
        if (!isset($users['admin'])) {
            $users['admin'] = [
                'username' => 'admin',
                'password' => password_hash('123456', PASSWORD_BCRYPT),
                'id' => 'admin_001',
                'status' => 'Администратор системы',
                'role' => 'admin',
                'achievements' => []
            ];
            $this->storage->write($this->filename, $users);
        }
    }

    public function register($username, $password) {
        $this->seedAdmin();
        $users = $this->storage->read($this->filename);
        if (isset($users[$username])) {
            return ['success' => false, 'message' => 'Пользователь уже существует'];
        }

        $users[$username] = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'id' => $this->storage->generateId(),
            'status' => '',
            'role' => 'user',
            'achievements' => []
        ];

        if ($this->storage->write($this->filename, $users)) {
            return ['success' => true, 'message' => 'Регистрация успешна'];
        }
        return ['success' => false, 'message' => 'Ошибка при сохранении'];
    }

    public function login($username, $password) {
        $this->seedAdmin();
        $users = $this->storage->read($this->filename);
        if (!isset($users[$username]) || !password_verify($password, $users[$username]['password'])) {
            return ['success' => false, 'message' => 'Неверное имя пользователя или пароль'];
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user'] = [
            'id' => $users[$username]['id'],
            'username' => $username,
            'role' => isset($users[$username]['role']) ? $users[$username]['role'] : 'user'
        ];

        return ['success' => true, 'user' => $_SESSION['user']];
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        return ['success' => true];
    }

    public function getCurrentUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    public function listUsers() {
        $users = $this->storage->read($this->filename);
        $list = [];
        foreach ($users as $user) {
            $list[] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'status' => isset($user['status']) ? $user['status'] : '',
                'avatar' => isset($user['avatar']) ? $user['avatar'] : null,
                'bio' => isset($user['bio']) ? $user['bio'] : '',
                'last_seen' => isset($user['last_seen']) ? $user['last_seen'] : 0,
                'typing_in' => isset($user['typing_in']) ? $user['typing_in'] : null
            ];
        }
        return $list;
    }

    public function updateProfile($user_id, $data) {
        $users = $this->storage->read($this->filename);
        foreach ($users as &$user) {
            if ($user['id'] === $user_id) {
                if (isset($data['status'])) $user['status'] = $data['status'];
                if (isset($data['bio'])) $user['bio'] = $data['bio'];
                if (isset($data['avatar'])) $user['avatar'] = $data['avatar'];
                if (isset($data['typing_in'])) $user['typing_in'] = $data['typing_in'];
                $user['last_seen'] = time();
                break;
            }
        }
        return $this->storage->write($this->filename, $users);
    }

    public function updateStatus($user_id, $status) {
        return $this->updateProfile($user_id, ['status' => $status]);
    }
}
