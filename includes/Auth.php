<?php
require_once __DIR__ . '/Storage.php';

class Auth {
    private static $secret = "php-crm-hop-very-secret-key-12345";

    private static function log($msg) {
        $logFile = __DIR__ . '/../data/auth_debug.log';
        $time = date('c');
        file_put_contents($logFile, "[$time] $msg\n", FILE_APPEND);
    }

    public static function login($login, $password, $storage) {
        $login = trim((string)$login);
        if (empty($login)) {
            self::log("Login attempt with empty login field");
            return null;
        }

        $users = $storage->readCollection('users');
        if (empty($users)) {
            self::log("Login failed: users collection is empty");
        }

        $foundUser = null;
        foreach ($users as $u) {
            if (!isset($u['login'])) continue;

            $dbLogin = trim((string)$u['login']);
            if (strcasecmp($dbLogin, $login) === 0) {
                $foundUser = $u;
                break;
            }
        }

        if (!$foundUser) {
            self::log("User not found: $login");
            return null;
        }

        $isActive = isset($foundUser['is_active']) ? (int)$foundUser['is_active'] : 1;
        if ($isActive !== 1) {
            self::log("User account inactive: $login");
            return null;
        }

        if (password_verify($password, $foundUser['password_hash'])) {
            unset($foundUser['password_hash']);
            $payload = [
                'id' => $foundUser['id'],
                'role' => $foundUser['role'],
                'department_id' => isset($foundUser['department_id']) ? $foundUser['department_id'] : null,
                'exp' => time() + 86400
            ];
            $jsonPayload = json_encode($payload);
            $signature = hash_hmac('sha256', $jsonPayload, self::$secret);
            $token = base64_encode($jsonPayload) . '.' . $signature;
            self::log("Login successful: $login");
            return ['token' => $token, 'user' => $foundUser];
        }

        self::log("Password mismatch for user: $login");
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
            foreach ($headers as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    $token = str_replace('Bearer ', '', $value);
                    break;
                }
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
