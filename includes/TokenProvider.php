<?php
class TokenProvider {
    private static $secretFile = __DIR__ . '/../data/settings.json';

    private static function getSecret() {
        if (file_exists(self::$secretFile)) {
            $settings = json_decode(file_get_contents(self::$secretFile), true);
            if (!empty($settings['jwt_secret'])) {
                return $settings['jwt_secret'];
            }
        }

        $secret = bin2hex(random_bytes(32));
        $settings = file_exists(self::$secretFile) ? json_decode(file_get_contents(self::$secretFile), true) : [];
        $settings['jwt_secret'] = $secret;

        if (!is_dir(dirname(self::$secretFile))) {
            mkdir(dirname(self::$secretFile), 0777, true);
        }
        file_put_contents(self::$secretFile, json_encode($settings));
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
        $decodedSignature = self::base64UrlDecode($signature);
        $expectedSignature = hash_hmac('sha256', $header . "." . $payload, self::getSecret(), true);

        if (!hash_equals($decodedSignature, $expectedSignature)) return false;

        $payloadData = json_decode(self::base64UrlDecode($payload), true);
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) return false;

        return $payloadData;
    }

    private static function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64UrlDecode($data) {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
