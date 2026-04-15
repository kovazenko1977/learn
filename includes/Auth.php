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
        $settings = Storage::read('settings');
        if (isset($settings['pin_hash']) && password_verify($pin, $settings['pin_hash'])) {
            $_SESSION['authenticated'] = true;
            $_SESSION['last_activity'] = time();
            Storage::log('Login successful');
            return true;
        }
        Storage::log('Failed login attempt');
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
        // Session timeout (2 hours)
        if (time() - $_SESSION['last_activity'] > 7200) {
            self::logout();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }

    public static function requireAuth() {
        if (!self::check()) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }
}
