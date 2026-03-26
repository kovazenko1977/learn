<?php
session_start();

class AuthManager {
    public static function login($code) {
        require_once 'Storage.php';
        $settingsStorage = new Storage('settings.json');
        $settings = $settingsStorage->read();
        $storedPasscode = $settings['admin_passcode'] ?? '123456';

        if ($code === $storedPasscode) {
            $_SESSION['authenticated'] = true;
            return true;
        }
        return false;
    }

    public static function check() {
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            header('Location: login.php');
            exit;
        }
    }

    public static function logout() {
        session_destroy();
        header('Location: login.php');
        exit;
    }
}
