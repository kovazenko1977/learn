<?php
/**
 * Auth Middleware & JWT Token Provider
 */

require_once __DIR__ . '/Storage.php';

class Auth {
    private static string $secretKey = 'crm_secret_key_2026_super_secure_hash_892374829';

    public static function getSecret(): string {
        $storage = Storage::getInstance();
        $settings = $storage->get('settings');
        return $settings['jwt_secret'] ?? self::$secretKey;
    }

    /**
     * Generate JWT Token
     */
    public static function generateToken(array $user): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'sub' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'department' => $user['department'] ?? '',
            'iat' => time(),
            'exp' => time() + (86400 * 7) // 7 days token
        ]);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getSecret(), true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Validate JWT Token from request header or query
     */
    public static function validateToken(?string $jwtToken = null): ?array {
        if (!$jwtToken) {
            // Check Authorization Header safely
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

            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
                $jwtToken = $matches[1];
            } elseif (isset($_GET['token'])) {
                $jwtToken = $_GET['token'];
            }
        }

        if (!$jwtToken) return null;

        $tokenParts = explode('.', $jwtToken);
        if (count($tokenParts) !== 3) return null;

        $header = self::base64UrlDecode($tokenParts[0]);
        $payload = self::base64UrlDecode($tokenParts[1]);
        $signatureProvided = $tokenParts[2];

        $payloadData = json_decode($payload, true);
        if (!$payloadData) return null;

        // Check expiration
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return null;
        }

        // Verify signature
        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payload);
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getSecret(), true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        if (!hash_equals($base64UrlSignature, $signatureProvided)) {
            return null;
        }

        // Fetch current active user state from Storage
        $storage = Storage::getInstance();
        $user = $storage->getById('users', $payloadData['sub']);

        if (!$user || !($user['active'] ?? true)) {
            return null;
        }

        return $user;
    }

    /**
     * Helper to require authenticated user or output JSON error & exit
     */
    public static function requireUser(?string $requiredRole = null): array {
        $user = self::validateToken();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Необходима авторизация']);
            exit;
        }

        if ($requiredRole && $user['role'] !== 'admin' && $user['role'] !== $requiredRole) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Недостаточно прав доступа']);
            exit;
        }

        return $user;
    }

    private static function base64UrlEncode(string $text): string {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
    }

    private static function base64UrlDecode(string $text): string {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $text));
    }
}
