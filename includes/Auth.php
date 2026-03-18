<?php
session_start();
require_once 'Storage.php';

class Auth {
    public static function login($username, $password) {
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['user'] = ['id' => 'admin', 'name' => 'Администратор', 'role' => 'admin'];
            return true;
        }

        $clients = Storage::read('clients');
        foreach ($clients as $client) {
            if ($client['username'] === $username && password_verify($password, $client['password'])) {
                $_SESSION['user'] = array_merge($client, ['role' => 'client']);
                return true;
            }
        }
        return false;
    }

    public static function logout() {
        session_destroy();
    }

    public static function check() {
        return isset($_SESSION['user']);
    }

    public static function user() {
        return $_SESSION['user'] ?? null;
    }

    public static function isAdmin() {
        return (self::user()['role'] ?? '') === 'admin';
    }
}
