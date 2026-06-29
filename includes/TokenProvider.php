<?php

class TokenProvider {
    private static function getSecret() {
        $secret = getenv('JWT_SECRET');
        if (!$secret) {
            $secret = 'sanatorium20_fallback_secret_key'; // Fallback for local dev
        }
        return $secret;
    }

    public static function generateToken($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['exp'] = time() + (60 * 60 * 24); // 24 hours
        $payload = json_encode($payload);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getSecret(), true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        list($header, $payload, $signature) = $parts;

        $validSignature = hash_hmac('sha256', $header . "." . $payload, self::getSecret(), true);
        $validSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));

        if ($signature !== $validSignature) return null;

        $decodedPayload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
        if ($decodedPayload['exp'] < time()) return null;

        return $decodedPayload;
    }

    public static function getCurrentUser() {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return self::validateToken($matches[1]);
        }
        return null;
    }
}
