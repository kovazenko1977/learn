<?php
require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/TokenProvider.php';

class Auth {
    public static function authenticate($username, $password) {
        $users = Storage::get('users');
        foreach ($users as $user) {
            if ($user['username'] === $username && password_verify($password, $user['password_hash'])) {
                unset($user['password_hash']);
                $token = TokenProvider::generateToken($user);
                return ['user' => $user, 'token' => $token];
            }
        }
        return false;
    }

    public static function getUser() {
        $headers = getallheaders();
        $token = null;

        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                $token = $matches[1];
            }
        } elseif (isset($_GET['token'])) {
            $token = $_GET['token'];
        }

        if (!$token) return null;

        return TokenProvider::validateToken($token);
    }

    public static function checkRole($roles) {
        $user = self::getUser();
        if (!$user) return false;
        if (is_array($roles)) {
            return in_array($user['role'], $roles);
        }
        return $user['role'] === $roles;
    }
}
