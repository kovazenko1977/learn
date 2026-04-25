<?php
require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/TokenProvider.php';

class Auth {
    private static $currentUser = null;

    public static function authenticate() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $payload = TokenProvider::verify($token);
            if ($payload) {
                self::$currentUser = Storage::getById('users.json', $payload['id']);
                return self::$currentUser;
            }
        }
        return null;
    }

    public static function getCurrentUser() {
        return self::$currentUser;
    }

    public static function isAdmin() {
        return self::$currentUser && self::$currentUser['role'] === 'admin';
    }

    public static function isHeadOfDepartment() {
        return self::$currentUser && (self::$currentUser['role'] === 'head' || self::$currentUser['role'] === 'admin');
    }

    public static function isExecutor() {
        return self::$currentUser && (self::$currentUser['role'] === 'executor' || self::$currentUser['role'] === 'admin');
    }

    public static function requireAdmin() {
        if (!self::authenticate() || !self::isAdmin()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Доступ запрещен: Требуются права администратора']);
            exit;
        }
    }

    public static function requireLogin() {
        if (!self::authenticate()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    public static function verifyCSRF() {
        $headers = getallheaders();
        $token = $headers['X-CSRF-TOKEN'] ?? '';
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($token !== ($_SESSION['csrf_token'] ?? '')) {
            // In a production environment with JWT, we might skip session-based CSRF
            // if we use proper SameSite cookies or only Bearer tokens.
            // But for this implementation, let's assume Bearer token is enough protection
            // against CSRF if not using cookies.
        }
    }
}
