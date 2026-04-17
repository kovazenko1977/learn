<?php

class Auth {
    public static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login($user, $password) {
        if ($user && Security::verifyPassword($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                return false;
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            return true;
        }
        return false;
    }

    public static function logout() {
        session_destroy();
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public static function getRole() {
        return isset($_SESSION['role']) ? $_SESSION['role'] : null;
    }

    public static function requireAuth() {
        if (!self::isLoggedIn()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    public static function requireRole($roles) {
        self::requireAuth();
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        if (!in_array(self::getRole(), $roles) && self::getRole() !== 'admin') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
    }
}
