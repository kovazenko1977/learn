<?php
/**
 * Auth & Session Management for МедСервис
 */

require_once __DIR__ . '/Storage.php';

class Auth {
    public const ROLE_EMPLOYEE = 'Employee';       // Сотрудник
    public const ROLE_EXECUTOR = 'Executor';       // Исполнитель
    public const ROLE_SERVICE_HEAD = 'Service Head'; // Руководитель службы
    public const ROLE_DISPATCHER = 'Dispatcher';   // Диспетчер
    public const ROLE_ADMIN = 'Admin';             // Администратор

    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.use_only_cookies', '1');
            session_start();
        }
    }

    public static function normalizePhone(string $phone): string {
        $clean = preg_replace('/[^0-9+]/', '', $phone);
        if (empty($clean)) return '';
        if ($clean[0] !== '+' && strlen($clean) === 12 && str_starts_with($clean, '375')) {
            $clean = '+' . $clean;
        }
        return $clean;
    }

    public static function hashPassword(string $pincode): string {
        return password_hash($pincode, PASSWORD_BCRYPT);
    }

    public static function verifyPassword(string $pincode, string $hash): bool {
        return password_verify($pincode, $hash);
    }

    public static function getCurrentUser(): ?array {
        self::initSession();

        $storage = StorageProvider::getInstance();

        // 1. Check session
        if (isset($_SESSION['user_id'])) {
            $user = $storage->findOne('users', fn($u) => isset($u['id']) && $u['id'] == $_SESSION['user_id'] && empty($u['is_blocked']));
            if ($user) return $user;
        }

        // 2. Check Authorization header
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            $token = $matches[1];
            $user = $storage->findOne('users', fn($u) => isset($u['token']) && $u['token'] === $token && empty($u['is_blocked']));
            if ($user) return $user;
        }

        return null;
    }

    public static function login(string $phone, string $pincode): ?array {
        self::initSession();
        $storage = StorageProvider::getInstance();
        $phoneNorm = self::normalizePhone($phone);

        $user = $storage->findOne('users', fn($u) => self::normalizePhone($u['phone'] ?? '') === $phoneNorm && empty($u['is_blocked']));

        if (!$user) return null;

        if (!self::verifyPassword($pincode, $user['password'])) {
            self::auditLog('LOGIN_FAILED', null, "Failed login attempt for phone $phoneNorm");
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $storage->update('users', $user['id'], [
            'token' => $token,
            'last_active' => date('Y-m-d H:i:s')
        ]);

        $_SESSION['user_id'] = $user['id'];
        $user['token'] = $token;
        unset($user['password']);

        self::auditLog('LOGIN_SUCCESS', $user['id'], "User logged in: {$user['name']}");

        return $user;
    }

    public static function register(array $data): array {
        $storage = StorageProvider::getInstance();
        $phoneNorm = self::normalizePhone($data['phone'] ?? '');

        if (empty($phoneNorm)) {
            throw new Exception('Неверный номер телефона');
        }

        $existing = $storage->findOne('users', fn($u) => self::normalizePhone($u['phone'] ?? '') === $phoneNorm);
        if ($existing) {
            throw new Exception('Пользователь с таким номером телефона уже зарегистрирован');
        }

        $pin = trim($data['password'] ?? '');
        if (strlen($pin) < 4) {
            throw new Exception('Пароль должен содержать минимум 4-6 цифр');
        }

        $newUser = [
            'name' => trim($data['name'] ?? ''),
            'phone' => $phoneNorm,
            'department_id' => (int)($data['department_id'] ?? 1),
            'department_name' => trim($data['department_name'] ?? 'Общий отдел'),
            'position' => trim($data['position'] ?? 'Сотрудник'),
            'role' => $data['role'] ?? self::ROLE_EMPLOYEE,
            'service_id' => (int)($data['service_id'] ?? 0),
            'password' => self::hashPassword($pin),
            'avatar' => '',
            'hide_phone' => false,
            'is_blocked' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'last_active' => date('Y-m-d H:i:s'),
            'notification_settings' => [
                'new_requests' => true,
                'status_change' => true,
                'private_messages' => true,
                'general_chat' => true,
                'assignments' => true,
                'emergency_alerts' => true,
                'sla_overdue' => true
            ]
        ];

        $created = $storage->insert('users', $newUser);
        self::auditLog('USER_REGISTERED', $created['id'], "Registered new user {$created['name']}");

        return $created;
    }

    public static function logout(): void {
        self::initSession();
        $user = self::getCurrentUser();
        if ($user) {
            self::auditLog('LOGOUT', $user['id'], "User logged out: {$user['name']}");
            $storage = StorageProvider::getInstance();
            $storage->update('users', $user['id'], ['token' => '']);
        }
        session_destroy();
    }

    public static function auditLog(string $action, $userId = null, string $details = ''): void {
        $storage = StorageProvider::getInstance();
        $storage->insert('audit', [
            'action' => $action,
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
