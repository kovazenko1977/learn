<?php
require_once __DIR__ . '/Storage.php';

class Auth {
    private static $secret = "php-crm-hop-very-secret-key-12345";

    public static function login($login, $password, $storage) {
        $login = trim((string)$login);
        $users = $storage->readCollection('users');

        // Manual search for better resilience (case-insensitive and type-safe)
        $user = null;
        foreach ($users as $u) {
            if (isset($u['login']) && strcasecmp(trim($u['login']), $login) === 0) {
                $user = $u;
                break;
            }
        }

        if (!$user) return null;

        if (!isset($user['is_active']) || (int)$user['is_active'] !== 1) {
            return null;
        }

        // Handle potentially different password hashing algos if migrated
        if (password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            $payload = [
                'id' => $user['id'],
                'role' => $user['role'],
                'department_id' => isset($user['department_id']) ? $user['department_id'] : null,
                'exp' => time() + 86400
            ];
            $jsonPayload = json_encode($payload);
            $signature = hash_hmac('sha256', $jsonPayload, self::$secret);
            $token = base64_encode($jsonPayload) . '.' . $signature;
            return ['token' => $token, 'user' => $user];
        }
        return null;
    }

    public static function check($roles = []) {
        $token = null;

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $token = str_replace('Bearer ', '', $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        } elseif (isset($_GET['token'])) {
            $token = $_GET['token'];
        }

        if (!$token) {
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            if (isset($headers['Authorization'])) {
                $token = str_replace('Bearer ', '', $headers['Authorization']);
            } elseif (isset($headers['authorization'])) {
                $token = str_replace('Bearer ', '', $headers['authorization']);
            }
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
        if (!$decoded || !isset($decoded['exp']) || $decoded['exp'] < time()) return null;

        if (!empty($roles) && (!isset($decoded['role']) || !in_array($decoded['role'], $roles))) return null;

        return $decoded;
    }
}
