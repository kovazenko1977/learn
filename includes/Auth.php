<?php
require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/TokenProvider.php';

class Auth {
    private static $currentUser = null;

    public static function init() {
        if (self::$currentUser !== null) return;

        $token = TokenProvider::getBearerToken();
        if ($token) {
            $userData = TokenProvider::validate($token);
            if ($userData) {
                self::$currentUser = $userData;
            }
        }
    }

    public static function login($username, $password) {
        $users = Storage::read('users');
        foreach ($users as $user) {
            if (($user['username'] ?? '') === $username) {
                if (password_verify($password, $user['password'] ?? '')) {
                    $tokenData = [
                        'id' => $user['id'],
                        'name' => $user['name'] ?? $user['username'],
                        'role' => $user['role']
                    ];
                    return TokenProvider::generate($tokenData);
                }
            }
        }
        return false;
    }

    public static function check() {
        self::init();
        return self::$currentUser !== null;
    }

    public static function getUser() {
        self::init();
        return self::$currentUser;
    }

    public static function requireAuth() {
        if (!self::check()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    public static function requireRole($roles) {
        self::requireAuth();
        if (!is_array($roles)) $roles = [$roles];
        if (!in_array(self::$currentUser['role'], $roles) && self::$currentUser['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
    }

    public static function requireAdmin() {
        self::requireRole('admin');
    }

    public static function logout() {
        // Token based logout is handled on client side by removing token
        // Server side could blacklist token if needed
    }
}
