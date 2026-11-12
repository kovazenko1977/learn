<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';

class AuthHelper {
    private static string $jwtSecret = 'CRM_SECURE_JWT_SECRET_2025_#*!99812_MAINTENANCE_SYS';

    public static function getSecret(): string {
        $envSecret = getenv('JWT_SECRET');
        return !empty($envSecret) ? $envSecret : self::$jwtSecret;
    }

    public static function generateToken(array $payload, int $ttlSeconds = 86400 * 7): string {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $ttlSeconds;

        $base64UrlHeader = self::base64UrlEncode(json_encode($header));
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', "{$base64UrlHeader}.{$base64UrlPayload}", self::getSecret(), true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return "{$base64UrlHeader}.{$base64UrlPayload}.{$base64UrlSignature}";
    }

    public static function validateToken(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $sigB64] = $parts;

        $sig = self::base64UrlDecode($sigB64);
        $expectedSig = hash_hmac('sha256', "{$headerB64}.{$payloadB64}", self::getSecret(), true);

        if (!hash_equals($expectedSig, $sig)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }

    public static function getAuthenticatedUser(StorageInterface $storage): ?array {
        $headers = self::getRequestHeaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (empty($authHeader) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            $payload = self::validateToken($matches[1]);
            if ($payload && isset($payload['sub'])) {
                $user = $storage->findById('users', $payload['sub']);
                if ($user && ($user['status'] ?? 'active') === 'active') {
                    return $user;
                }
            }
        }
        return null;
    }

    public static function getRequestHeaders(): array {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                return $headers;
            }
        }
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    public static function requireAuth(StorageInterface $storage, ?array $allowedRoles = null): array {
        $user = self::getAuthenticatedUser($storage);
        if (!$user) {
            self::jsonResponse(['success' => false, 'error' => 'Unauthorized. Please login.'], 401);
            exit;
        }

        if ($allowedRoles !== null && !in_array($user['role'], $allowedRoles, true)) {
            self::jsonResponse(['success' => false, 'error' => 'Forbidden. Insufficient permissions.'], 403);
            exit;
        }

        return $user;
    }

    public static function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function logAction(StorageInterface $storage, ?array $user, string $action, string $details = ''): void {
        try {
            $storage->insert('logs', [
                'user_id' => $user['id'] ?? null,
                'user_name' => $user['username'] ?? 'anonymous',
                'action' => $action,
                'details' => $details,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Throwable $e) {
            error_log("Failed to write log: " . $e->getMessage());
        }
    }

    public static function createNotification(
        StorageInterface $storage,
        int $userId,
        string $type,
        string $title,
        string $message,
        ?int $ticketId = null
    ): void {
        try {
            $storage->insert('notifications', [
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'ticket_id' => $ticketId,
                'is_read' => false,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Throwable $e) {
            error_log("Failed to create notification: " . $e->getMessage());
        }
    }
}
