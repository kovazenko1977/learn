<?php
require_once __DIR__ . '/Storage.php';

class TokenProvider {
    private static function getSecret() {
        $settings = Storage::get('settings');
        if (isset($settings['jwt_secret'])) {
            return $settings['jwt_secret'];
        }

        $secret = bin2hex(random_bytes(32));
        $settings['jwt_secret'] = $secret;
        Storage::set('settings', $settings);
        return $secret;
    }

    public static function generateToken($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['iat'] = time();
        $payload['exp'] = time() + (60 * 60 * 24 * 7); // 7 days

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getSecret(), true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        list($header, $payload, $signature) = $parts;

        $validSignature = hash_hmac('sha256', $header . "." . $payload, self::getSecret(), true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));

        if ($signature !== $base64UrlSignature) return null;

        $data = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
        if ($data['exp'] < time()) return null;

        return $data;
    }
}
