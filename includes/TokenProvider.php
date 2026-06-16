<?php

class TokenProvider {
    private $secret;

    public function __construct($settings = []) {
        $this->secret = $settings['JWT_SECRET']
            ?? getenv('JWT_SECRET')
            ?? 'CRM_SECURE_FALLBACK_KEY_2024';
    }

    public function generate($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['exp'] = time() + (86400 * 7); // 1 week
        $payload = json_encode($payload);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function validate($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;

        list($header, $payload, $signature) = $parts;
        $validSignature = hash_hmac('sha256', $header . "." . $payload, $this->secret, true);
        $validSignature = $this->base64UrlEncode($validSignature);

        if (!hash_equals($validSignature, $signature)) return false;

        $data = json_decode($this->base64UrlDecode($payload), true);
        if (isset($data['exp']) && $data['exp'] < time()) return false;

        return $data;
    }

    private function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function base64UrlDecode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
