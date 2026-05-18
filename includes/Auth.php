<?php
require_once __DIR__ . '/Storage.php';

class Auth {
    public static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login($pin) {
        self::init();
        $settings = Storage::read('settings.json');
        if (password_verify($pin, $settings['pin_hash'])) {
            $_SESSION['authenticated'] = true;
            return true;
        }
        return false;
    }

    public static function logout() {
        self::init();
        session_destroy();
    }

    public static function check() {
        self::init();
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    }

    public static function requireAuth() {
        if (!self::check()) {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }
}
