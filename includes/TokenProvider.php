<?php

class TokenProvider {
    private static string $secretFile = __DIR__ . '/../data/.secret';

    private static function getSecret(): string {
        $envSecret = getenv('JWT_SECRET');
        if (!empty($envSecret)) {
            return $envSecret;
        }

        if (file_exists(self::$secretFile)) {
            $secret = trim(file_get_contents(self::$secretFile));
            if (!empty($secret)) {
                return $secret;
            }
        }

        $secret = bin2hex(random_bytes(32));
        if (!is_dir(dirname(self::$secretFile))) {
            mkdir(dirname(self::$secretFile), 0755, true);
        }
        file_put_contents(self::$secretFile, $secret);
        return $secret;
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function generateToken(array $payload, int $expiresIn = 86400): string {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiresIn;

        $base64Header = self::base64UrlEncode(json_encode($header));
        $base64Payload = self::base64UrlEncode(json_encode($payload));

        $secret = self::getSecret();
        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", $secret, true);
        $base64Signature = self::base64UrlEncode($signature);

        return "$base64Header.$base64Payload.$base64Signature";
    }

    public static function verifyToken(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        list($base64Header, $base64Payload, $base64Signature) = $parts;

        $secret = self::getSecret();
        $expectedSignature = self::base64UrlEncode(hash_hmac('sha256', "$base64Header.$base64Payload", $secret, true));

        if (!hash_equals($expectedSignature, $base64Signature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        if (!$payload || !isset($payload['exp'])) {
            return null;
        }

        if (time() > $payload['exp']) {
            return null;
        }

        return $payload;
    }
}
