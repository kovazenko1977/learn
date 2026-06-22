<?php
require_once __DIR__ . '/Storage.php';

class Auth {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login($pin) {
        self::init();
        $users = Storage::read('users');
        foreach ($users as $user) {
            if (isset($user['pin_hash']) && password_verify($pin, $user['pin_hash'])) {
                $_SESSION['authenticated'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'] ?? 'manager';
                $_SESSION['user_name'] = $user['name'] ?? 'User';
                $_SESSION['last_activity'] = time();
                Storage::log('Login successful: ' . $_SESSION['user_name'], $_SESSION['user_id']);
                return true;
            }
        }
        Storage::log('Failed login attempt', 'guest');
        return false;
    }

    public static function logout() {
        self::init();
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function check() {
        self::init();
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            return false;
        }
        if (time() - $_SESSION['last_activity'] > 7200) {
            self::logout();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }

    public static function getUser() {
        self::init();
        if (!self::check()) return null;
        return [
            'id' => $_SESSION['user_id'],
            'role' => $_SESSION['user_role'],
            'name' => $_SESSION['user_name']
        ];
    }

    public static function requireAuth() {
        if (!self::check()) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    public static function requireAdmin() {
        self::requireAuth();
        if ($_SESSION['user_role'] !== 'admin') {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: Admin only']);
            exit;
        }
    }
}
