<?php
require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/TokenProvider.php';

class Auth {
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

        if (!$token) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $payload = TokenProvider::validateToken($token);
        if (!$payload) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit;
        }

        return $payload;
    }

    public static function login($username, $password) {
        $users = Storage::read('users');
        foreach ($users as $user) {
            if ($user['username'] === $username && password_verify($password, $user['password'])) {
                unset($user['password']);
                return TokenProvider::generateToken($user);
            }
        }
        return false;
    }

    public static function checkRole($user, $allowedRoles) {
        if (!in_array($user['role'], $allowedRoles)) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
    }
}
