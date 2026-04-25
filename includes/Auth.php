<?php
require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/TokenProvider.php';

class Auth {
    public static function authenticate() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $settings = Storage::getInstance()->getSettings();
            TokenProvider::setSecret($settings['jwt_secret']);

            $payload = TokenProvider::validate($token);
            if ($payload) {
                return $payload;
            }
        }

        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    public static function checkRole($user, $allowedRoles) {
        if (!in_array($user['role'], $allowedRoles)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
    }
}
