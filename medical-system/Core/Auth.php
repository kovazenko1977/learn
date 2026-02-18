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

    public static function loginByCode($code) {
        $jsonStore = new JsonStore('staff');
        $staff = $jsonStore->getAll();

        foreach ($staff as $user) {
            if (isset($user['access_code']) && $user['access_code'] === $code) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'role' => $user['role'],
                    'name' => $user['name']
                ];
                (new Managers\LogManager())->log('Вход в систему');
                return true;
            }
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

    public static function isAdmin() {
        return self::hasRole('admin');
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
