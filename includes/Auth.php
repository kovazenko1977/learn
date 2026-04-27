<?php
require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/TokenProvider.php';

class Auth {
    private static $currentUser = null;

    public static function authenticate() {
        $headers = getallheaders();
        $token = null;
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                $token = $matches[1];
            }
        } elseif (isset($_GET['token'])) {
            $token = $_GET['token'];
        }

        if (!$token) return false;
        $payload = TokenProvider::validateToken($token);
        if (!$payload) return false;

        $users = Storage::getData('users');
        foreach ($users as $user) {
            if ($user['id'] === $payload['id']) {
                unset($user['password_hash']);
                self::$currentUser = $user;
                return $user;
            }
        }
        return false;
    }

    public static function login($username, $password) {
        $users = Storage::getData('users');
        foreach ($users as $user) {
            if ($user['username'] === $username && password_verify($password, $user['password_hash'])) {
                unset($user['password_hash']);
                return TokenProvider::generateToken($user);
            }
        }
        return false;
    }

    public static function user() {
        return self::$currentUser;
    }

    public static function checkRole($roles) {
        if (!self::$currentUser) return false;
        if (is_string($roles)) $roles = [$roles];
        return in_array(self::$currentUser['role'], $roles);
    }

    public static function isAdmin() {
        return self::checkRole('Administrator');
    }
}