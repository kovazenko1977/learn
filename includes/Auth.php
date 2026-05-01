<?php

class Auth {
    private static $secret = null;

    private static function getSecret() {
        if (self::$secret === null) {
            $configFile = __DIR__ . '/../data/config.json';
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                self::$secret = $config['jwt_secret'] ?? 'default-fallback-secret-replace-me';
            } else {
                self::$secret = 'default-fallback-secret-replace-me';
            }
        }
        return self::$secret;
    }

    public static function generateToken($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['exp'] = time() + (60 * 60 * 24); // 24 hours

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getSecret(), true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;

        $header = $parts[0];
        $payload = $parts[1];
        $signature = $parts[2];

        $validSignature = self::base64UrlEncode(hash_hmac('sha256', $header . "." . $payload, self::getSecret(), true));

        if ($signature !== $validSignature) return false;

        $payloadData = json_decode(self::base64UrlDecode($payload), true);
        if (!$payloadData || (isset($payloadData['exp']) && $payloadData['exp'] < time())) {
            return false;
        }

        return $payloadData;
    }

    public static function getBearerToken() {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }

        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        return $_GET['token'] ?? null;
    }

    public static function checkRole($requiredRoles) {
        $token = self::getBearerToken();
        if (!$token) {
            header('HTTP/1.0 401 Unauthorized');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $user = self::validateToken($token);
        if (!$user) {
            header('HTTP/1.0 401 Unauthorized');
            echo json_encode(['error' => 'Invalid or expired token']);
            exit;
        }

        if (!is_array($requiredRoles)) $requiredRoles = [$requiredRoles];

        if (!in_array($user['role'], $requiredRoles)) {
            header('HTTP/1.0 403 Forbidden');
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        return $user;
    }

    private static function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64UrlDecode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
