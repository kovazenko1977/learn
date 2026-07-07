<?php

class TokenProvider {
    private $secret;

    public function __construct() {
        $this->secret = getenv('JWT_SECRET') ?: 'sanatorium2_default_secret_2024';
    }

    public function generateToken($payload) {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload['iat'] = time();
        $payload['exp'] = time() + (86400 * 7); // 7 days

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;

        list($header, $payload, $signature) = $parts;

        $validSignature = $this->base64UrlEncode(hash_hmac('sha256', $header . "." . $payload, $this->secret, true));

        if ($signature !== $validSignature) return false;

        $payloadData = json_decode($this->base64UrlDecode($payload), true);
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) return false;

        return $payloadData;
    }

    private function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function base64UrlDecode($data) {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
