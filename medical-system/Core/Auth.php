<?php

namespace Medical\Core;

class Auth {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function login($username, $password) {
        // Default credentials for demo
        if ($username === 'admin' && $password === 'admin') {
            $_SESSION['user'] = [
                'username' => 'admin',
                'role' => 'admin',
                'name' => 'Администратор'
            ];
            return true;
        }
        return false;
    }

    public static function loginByCode($code) {
        $codes = [
            '123456' => ['role' => 'admin', 'name' => 'Администратор'],
            '101010' => ['role' => 'doctor', 'name' => 'Лечащий врач'],
            '111111' => ['role' => 'consultant', 'name' => 'Врач-консультант'],
            '202020' => ['role' => 'cashier', 'name' => 'Кассир'],
            '303030' => ['role' => 'nurse', 'name' => 'Медсестра'],
            '404040' => ['role' => 'head', 'name' => 'Начальник медчасти'],
        ];

        if (isset($codes[$code])) {
            $_SESSION['user'] = [
                'username' => 'user_' . $code,
                'role' => $codes[$code]['role'],
                'name' => $codes[$code]['name']
            ];
            return true;
        }
        return false;
    }

    public static function logout() {
        session_destroy();
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user']);
    }

    public static function getUser() {
        return $_SESSION['user'] ?? null;
    }

    public static function hasRole($roles) {
        if (!self::isLoggedIn()) return false;
        if (is_string($roles)) $roles = [$roles];
        return in_array($_SESSION['user']['role'], $roles);
    }

    public static function checkCsrf($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function getCsrfToken() {
        return $_SESSION['csrf_token'] ?? '';
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
}
