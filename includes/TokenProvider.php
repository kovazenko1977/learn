<?php
class TokenProvider {
    private static function getSecret() {
        $settings = Storage::getSettings();
        if (isset($settings['jwt_secret'])) return $settings['jwt_secret'];

        $secret = bin2hex(random_bytes(32));
        $settings['jwt_secret'] = $secret;
        Storage::saveSettings($settings);
        return $secret;
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
        list($header, $payload, $signature) = $parts;
        $decodedPayload = json_decode(self::base64UrlDecode($payload), true);
        if (!$decodedPayload || (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time())) return false;

        $validSignature = self::base64UrlEncode(hash_hmac('sha256', $header . "." . $payload, self::getSecret(), true));
        if ($signature !== $validSignature) return false;
        return $decodedPayload;
    }

    private static function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64UrlDecode($data) {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}