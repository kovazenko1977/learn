<?php
// api/helpers.php
require_once __DIR__ . '/config.php';

function json_out($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function json_in(): array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return $_POST;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $_POST;
}

function clean($value) {
    if (is_string($value)) {
        return htmlspecialchars(trim($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    return $value;
}

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function jwt_encode(array $payload): string {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_TTL;

    $payloadStr = json_encode($payload);

    $base64Header = base64url_encode($header);
    $base64Payload = base64url_encode($payloadStr);

    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, JWT_SECRET, true);
    $base64Signature = base64url_encode($signature);

    return $base64Header . "." . $base64Payload . "." . $base64Signature;
}

function jwt_decode(string $jwt): ?array {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return null;
    }

    list($base64Header, $base64Payload, $base64Signature) = $parts;

    $signature = base64url_encode(hash_hmac('sha256', $base64Header . "." . $base64Payload, JWT_SECRET, true));
    if (!hash_equals($signature, $base64Signature)) {
        return null;
    }

    $payload = json_decode(base64url_decode($base64Payload), true);
    if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
        return null;
    }

    return $payload;
}

function get_bearer_token(): ?string {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/i', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function require_auth(bool $requireAdmin = false): array {
    $token = get_bearer_token();
    if (!$token) {
        json_out(['error' => 'Unauthorized: Token missing'], 401);
    }
    $payload = jwt_decode($token);
    if (!$payload) {
        json_out(['error' => 'Unauthorized: Token invalid or expired'], 401);
    }
    if ($requireAdmin && ($payload['role'] ?? '') !== 'admin') {
        json_out(['error' => 'Forbidden: Admin access required'], 403);
    }
    return $payload;
}

function get_current_user_optional(): ?array {
    $token = get_bearer_token();
    if (!$token) return null;
    return jwt_decode($token);
}

function esc(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Storage Helper Function
 */
function get_storage() {
    return new class {
        public function get($collection) {
            return Storage::getAll($collection);
        }

        public function find($collection, $id) {
            return Storage::getById($collection, $id);
        }

        public function where($collection, $criteria) {
            return Storage::findWhere($collection, $criteria);
        }

        public function insert($collection, $data) {
            $item = Storage::insert($collection, $data);
            return $item['id'] ?? null;
        }

        public function update($collection, $id, $data) {
            return Storage::update($collection, $id, $data);
        }

        public function delete($collection, $id) {
            return Storage::delete($collection, $id);
        }
    };
}
