<?php
session_start();

class Security {
    private static $settingsFile = __DIR__ . '/../data/settings.json';

    public static function checkAuth() {
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    public static function login($pin) {
        if (!file_exists(self::$settingsFile)) {
            return false;
        }
        $settings = json_decode(file_get_contents(self::$settingsFile), true);
        if (!$settings || !isset($settings['pin'])) {
            return false;
        }

        // Support both plaintext and hashed PIN for initial setup
        if ($settings['pin'] === $pin || password_verify($pin, $settings['pin'])) {
            $_SESSION['authenticated'] = true;
            return true;
        }
        return false;
    }

    public static function logout() {
        session_destroy();
    }

    public static function log($action) {
        $logFile = __DIR__ . '/../data/audit.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'];
        $entry = "[$timestamp] IP: $ip | Action: $action | UA: $ua\n";
        file_put_contents($logFile, $entry, FILE_APPEND);
    }
}
