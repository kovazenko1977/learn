<?php
// api/helpers.php

require_once __DIR__ . '/config.php';

function json_out($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function json_in() {
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $decoded = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }
    return array_merge($_GET, $_POST);
}

function clean_input($data) {
    if (is_array($data)) {
        foreach ($data as $key => $val) {
            $data[$key] = clean_input($val);
        }
        return $data;
    }
    if (is_string($data)) {
        return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
    }
    return $data;
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_encode($payload) {
    $header = ['typ' => 'JWT', 'alg' => 'HS256'];
    $base64UrlHeader = base64url_encode(json_encode($header));

    if (!isset($payload['exp'])) {
        $payload['exp'] = time() + JWT_EXPIRATION;
    }
    if (!isset($payload['iat'])) {
        $payload['iat'] = time();
    }

    $base64UrlPayload = base64url_encode(json_encode($payload));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = base64url_encode($signature);

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function jwt_decode($jwt) {
    $tokenParts = explode('.', $jwt);
    if (count($tokenParts) !== 3) {
        return false;
    }

    $header = base64url_decode($tokenParts[0]);
    $payload = base64url_decode($tokenParts[1]);
    $signatureProvided = $tokenParts[2];

    $expiration = json_decode($payload, true)['exp'] ?? 0;
    if ($expiration && (time() > $expiration)) {
        return false;
    }

    $base64UrlHeader = base64url_encode($header);
    $base64UrlPayload = base64url_encode($payload);
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = base64url_encode($signature);

    if ($base64UrlSignature === $signatureProvided) {
        return json_decode($payload, true);
    }

    return false;
}

function get_bearer_token() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } else if (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }

    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function require_auth() {
    $token = get_bearer_token();
    if (!$token) {
        json_out(['error' => 'Отсутствует токен авторизации (Unauthorized)'], 401);
    }
    $decoded = jwt_decode($token);
    if (!$decoded) {
        json_out(['error' => 'Недействительный или истекший токен'], 401);
    }
    return $decoded;
}

function require_admin() {
    $user = require_auth();
    if (($user['role'] ?? 'user') !== 'admin') {
        json_out(['error' => 'Доступ запрещен: требуется роль администратора'], 403);
    }
    return $user;
}
