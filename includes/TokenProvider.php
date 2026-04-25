<?php

class TokenProvider {
    private static function getSecret() {
        $settings = Storage::read('settings.json');
        if (isset($settings['JWT_SECRET'])) return $settings['JWT_SECRET'];

        $secret = getenv('JWT_SECRET');
        if ($secret) return $secret;

        // Generate and save a persistent secret if none exists
        $newSecret = bin2hex(random_bytes(32));
        $settings['JWT_SECRET'] = $newSecret;
        Storage::write('settings.json', $settings);
        return $newSecret;
    }

    public static function generate(array $payload): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['exp'] = time() + (3600 * 24); // 24 hours
        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getSecret(), true);
        $base64UrlSignature = self::base64UrlEncode($signature);
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function verify(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        list($header, $payload, $signature) = $parts;
        $validSignature = self::base64UrlEncode(hash_hmac('sha256', $header . "." . $payload, self::getSecret(), true));

        if ($signature !== $validSignature) return null;

        $decodedPayload = json_decode(self::base64UrlDecode($payload), true);
        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) return null;

        return $decodedPayload;
    }

    private static function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64UrlDecode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
