<?php
session_start();
require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/Security.php';

class Auth {
    public static function login($username, $password, $code = null) {
        // IP-based rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $limits = Storage::read('rate_limits');
        $ip_limit = isset($limits[$ip]) ? $limits[$ip] : ['attempts' => 0, 'time' => 0];

        if ($ip_limit['attempts'] > 10 && time() - $ip_limit['time'] < 300) {
            return ['success' => false, 'error' => 'Too many attempts. Blocked for 5 mins.'];
        }

        $user = Storage::findOne('users', ['username' => $username]);
        if (!$user || !password_verify($password, $user['password'])) {
            $ip_limit['attempts']++;
            $ip_limit['time'] = time();
            $limits[$ip] = $ip_limit;
            Storage::write('rate_limits', $limits);
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        if ($user['status'] === 'blocked') {
            return ['success' => false, 'error' => 'Account blocked'];
        }

        // Maintenance Mode Check
        $settings = Storage::read('settings');
        if (($settings['maintenance_mode'] ?? false) && $user['role'] === 'client') {
            return ['success' => false, 'error' => 'Maintenance mode active. Please try again later.'];
        }

        // 2FA check (Mocking TOTP-like behavior)
        // In a production environment, use a library like PHPGangsta_GoogleAuthenticator.
        if (!empty($user['two_fa_secret']) || $user['role'] === 'superadmin') {
            if (!$code) {
                return ['success' => false, '2fa_required' => true];
            }
            // Demo 2FA logic: '000000' is the universal bypass for testing.
            // For demo users, the code is also the last 6 chars of their ID.
            $demo_code = substr($user['id'], -6);
            if ($code !== '000000' && $code !== $demo_code) {
                Security::log('auth_fail_2fa', $user['id'], 'auth', ['username' => $username]);
                return ['success' => false, 'error' => 'Invalid 2FA code. For demo, use 000000.'];
            }
        }

        unset($limits[$ip]);
        Storage::write('rate_limits', $limits);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        Security::log('login', $user['id'], 'auth');
        return ['success' => true, 'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'details' => $user['details'] ?? [],
            'csrf_token' => $_SESSION['csrf_token']
        ]];
    }

    public static function logout() {
        if (isset($_SESSION['user_id'])) {
            Security::log('logout', $_SESSION['user_id'], 'auth');
        }
        session_destroy();
        return ['success' => true];
    }

    public static function check() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        if (time() - $_SESSION['last_activity'] > 1800) {
            self::logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    public static function requireRole($roles) {
        if (!self::check()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        if (!in_array($_SESSION['role'], (array)$roles)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if ($token !== $_SESSION['csrf_token']) {
                http_response_code(403);
                echo json_encode(['error' => 'CSRF validation failed']);
                exit;
            }
        }
    }

    public static function getCurrentUser() {
        if (!self::check()) return null;
        return [
            'id' => $_SESSION['user_id'],
            'role' => $_SESSION['role'],
            'username' => $_SESSION['username'],
            'csrf_token' => $_SESSION['csrf_token'] ?? null
        ];
    }
}
