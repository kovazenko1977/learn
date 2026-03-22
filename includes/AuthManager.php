<?php
require_once 'Storage.php';

class AuthManager {
    private $storage;
    private $filename = 'users';

    public function __construct() {
        $this->storage = new Storage();
    }

    public function register($username, $password) {
        $users = $this->storage->read($this->filename);
        if (isset($users[$username])) {
            return ['success' => false, 'message' => 'Пользователь уже существует'];
        }

        $users[$username] = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'id' => $this->storage->generateId()
        ];

        if ($this->storage->write($this->filename, $users)) {
            return ['success' => true, 'message' => 'Регистрация успешна'];
        }
        return ['success' => false, 'message' => 'Ошибка при сохранении'];
    }

    public function login($username, $password) {
        $users = $this->storage->read($this->filename);
        if (!isset($users[$username]) || !password_verify($password, $users[$username]['password'])) {
            return ['success' => false, 'message' => 'Неверное имя пользователя или пароль'];
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user'] = [
            'id' => $users[$username]['id'],
            'username' => $username
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
                'username' => $user['username']
            ];
        }
        return $list;
    }
}
