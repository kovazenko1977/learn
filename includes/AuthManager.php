<?php
session_start();

/**
 * AuthManager - Управление доступом к админ-панели
 */
class AuthManager {
    private static $passcode = '123456'; // Default admin passcode

    public static function login($code) {
        if ($code === self::$passcode) {
            $_SESSION['admin_auth'] = true;
            return true;
        }
        return false;
    }

    public static function logout() {
        unset($_SESSION['admin_auth']);
    }

    public static function check() {
        if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
            header('Location: login.php');
            exit;
        }
    }

    public static function isLoggedIn() {
        return isset($_SESSION['admin_auth']) && $_SESSION['admin_auth'] === true;
    }
}
