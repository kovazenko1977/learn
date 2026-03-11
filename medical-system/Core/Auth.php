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
                    'name' => $user['name'],
                    'specialization' => $user['specialization'] ?? '',
                    'permissions' => $user['permissions'] ?? []
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

    public static function can($permission) {
        if (!self::isLoggedIn()) return false;
        $user = self::getUser();

        // Admin has all rights
        if ($user['role'] === 'admin') return true;

        // Check granular permissions
        $permissions = $user['permissions'] ?? [];
        return in_array($permission, $permissions);
    }

    public static function canManageStaff() {
        return self::can('settings_staff');
    }

    public static function canSeeMoney() {
        return self::can('finance_view');
    }

    public static function canEditPatients() {
        return self::can('patients_edit');
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

    public static function requireLogin($allowedRoles = []) {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
        if (!empty($allowedRoles) && !self::hasRole($allowedRoles)) {
            die("У вас недостаточно прав для доступа к этой странице.");
        }
    }
}
