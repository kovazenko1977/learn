<?php
require_once 'Storage.php';

class Auth {
    private static $secret = "php-crm-hop-very-secret-key-12345";

    public static function login($login, $password, $storage) {
        $user = $storage->findOne('users', ['login' => $login, 'is_active' => 1]);
        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            $payload = ['id' => $user['id'], 'role' => $user['role'], 'exp' => time() + 86400];
            $jsonPayload = json_encode($payload);
            $signature = hash_hmac('sha256', $jsonPayload, self::$secret);
            $token = base64_encode($jsonPayload) . '.' . $signature;
            return ['token' => $token, 'user' => $user];
        }
        return null;
    }

    public static function check($roles = []) {
        $headers = getallheaders();
        $token = null;
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
        } elseif (isset($_GET['token'])) {
            $token = $_GET['token'];
        }

        if (!$token) return null;

        $parts = explode('.', $token);
        if (count($parts) !== 2) return null;

        $jsonPayload = base64_decode($parts[0]);
        $signature = $parts[1];

        if (hash_hmac('sha256', $jsonPayload, self::$secret) !== $signature) {
            return null;
        }

        $decoded = json_decode($jsonPayload, true);
        if (!$decoded || $decoded['exp'] < time()) return null;

        if (!empty($roles) && !in_array($decoded['role'], $roles)) return null;

        return $decoded;
    }
}
