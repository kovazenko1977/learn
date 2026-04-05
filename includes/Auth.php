<?php
session_start();

class Auth {
    private static $settings_file = __DIR__ . '/../data/settings.json';

    public static function login($code) {
        $settings = json_decode(file_get_contents(self::$settings_file), true);
        if ($code === $settings['access_code']) {
            $_SESSION['authenticated'] = true;
            return true;
        }
        return false;
    }

    public static function logout() {
        session_unset();
        session_destroy();
    }

    public static function check() {
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
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
