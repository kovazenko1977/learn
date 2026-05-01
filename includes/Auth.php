<?php
require_once __DIR__ . '/Storage.php';

class Auth {
    private static $secret = null;

    private static function getSecret() {
        if (self::$secret !== null) return self::$secret;

        $secretFile = __DIR__ . '/../data/secret.key';
        if (file_exists($secretFile)) {
            self::$secret = trim(file_get_contents($secretFile));
        }

        if (empty(self::$secret)) {
            self::$secret = bin2hex(random_bytes(32));
            file_put_contents($secretFile, self::$secret);
            @chmod($secretFile, 0600);
        }
        return self::$secret;
    }

    public static function forceLogin($user) {
        $payload = [
            'id' => $user['id'],
            'role' => $user['role'],
            'department_id' => $user['department_id'] ?? null,
            'permissions' => $user['permissions'] ?? null,
            'exp' => time() + 86400
        ];
        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, self::getSecret());
        $token = base64_encode($jsonPayload) . '.' . $signature;
        return ['token' => $token, 'user' => $user];
    }

    public static function login($login, $password, $storage) {
        $login = trim((string)$login);
        if (empty($login)) return null;

        $users = $storage->readCollection('users');
        $foundUser = null;
        foreach ($users as $u) {
            if (isset($u['login']) && strcasecmp(trim($u['login']), $login) === 0) {
                $foundUser = $u;
                break;
            }
        }

        if (!$foundUser) return null;
        if (isset($foundUser['is_active']) && (int)$foundUser['is_active'] !== 1) return null;

        if (password_verify($password, $foundUser['password_hash'])) {
            unset($foundUser['password_hash']);
            return self::forceLogin($foundUser);
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

        if (hash_hmac('sha256', $jsonPayload, self::getSecret()) !== $signature) return null;

        $decoded = json_decode($jsonPayload, true);
        if (!$decoded || !isset($decoded['exp']) || $decoded['exp'] < time()) return null;
        if (!empty($roles) && (!isset($decoded['role']) || !in_array($decoded['role'], $roles))) return null;

        return $decoded;
    }
}
