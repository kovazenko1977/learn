<?php

require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/TokenProvider.php';

class Auth {
    public const ROLE_ADMIN = 'admin';
    public const ROLE_HEAD = 'head';
    public const ROLE_RESPONSIBLE = 'responsible';
    public const ROLE_EXECUTOR = 'executor';

    public static function seedDefaultUsers(): void {
        $storage = Storage::getInstance();
        $users = $storage->getAll('users');

        if (empty($users)) {
            $defaultUsers = [
                [
                    'id' => 'user_admin_001',
                    'username' => 'admin',
                    'password_hash' => password_hash('admin123', PASSWORD_BCRYPT),
                    'full_name' => 'Администратор Системы',
                    'email' => 'admin@crm.local',
                    'role' => self::ROLE_ADMIN,
                    'department' => 'Руководство',
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 'user_head_001',
                    'username' => 'head',
                    'password_hash' => password_hash('head123', PASSWORD_BCRYPT),
                    'full_name' => 'Иванов Иван (Нач. Отдела)',
                    'email' => 'head@crm.local',
                    'role' => self::ROLE_HEAD,
                    'department' => 'Отдел ИТ и Ремонта',
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 'user_resp_001',
                    'username' => 'resp',
                    'password_hash' => password_hash('resp123', PASSWORD_BCRYPT),
                    'full_name' => 'Петров Петр (Ответственный)',
                    'email' => 'resp@crm.local',
                    'role' => self::ROLE_RESPONSIBLE,
                    'department' => 'Отдел Продаж',
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 'user_exec_001',
                    'username' => 'exec',
                    'password_hash' => password_hash('exec123', PASSWORD_BCRYPT),
                    'full_name' => 'Сидоров Алексей (Техник)',
                    'email' => 'exec@crm.local',
                    'role' => self::ROLE_EXECUTOR,
                    'department' => 'Отдел ИТ и Ремонта',
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ];

            foreach ($defaultUsers as $user) {
                $storage->save('users', $user);
            }
        }
    }

    public static function authenticate(string $username, string $password): ?array {
        self::seedDefaultUsers();
        $storage = Storage::getInstance();
        $users = $storage->getAll('users');

        foreach ($users as $user) {
            if ($user['username'] === $username && ($user['status'] ?? 'active') === 'active') {
                if (password_verify($password, $user['password_hash'])) {
                    unset($user['password_hash']);
                    $token = TokenProvider::generateToken([
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role'],
                        'full_name' => $user['full_name'],
                        'department' => $user['department'] ?? ''
                    ]);
                    $user['token'] = $token;
                    return $user;
                }
            }
        }
        return null;
    }

    public static function getCurrentUser(): ?array {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!$authHeader && isset($_GET['token'])) {
            $authHeader = 'Bearer ' . $_GET['token'];
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            $payload = TokenProvider::verifyToken($token);
            if ($payload) {
                $storage = Storage::getInstance();
                $user = $storage->getById('users', $payload['id']);
                if ($user && ($user['status'] ?? 'active') === 'active') {
                    unset($user['password_hash']);
                    return $user;
                }
            }
        }
        return null;
    }

    public static function generateResetToken(string $emailOrUsername): ?string {
        $storage = Storage::getInstance();
        $users = $storage->getAll('users');

        foreach ($users as $user) {
            if ($user['username'] === $emailOrUsername || ($user['email'] ?? '') === $emailOrUsername) {
                $resetToken = bin2hex(random_bytes(16));
                $user['reset_token'] = $resetToken;
                $storage->save('users', $user);
                return $resetToken;
            }
        }
        return null;
    }

    public static function resetPasswordWithToken(string $resetToken, string $newPassword): bool {
        $storage = Storage::getInstance();
        $users = $storage->getAll('users');

        foreach ($users as $user) {
            if (!empty($user['reset_token']) && $user['reset_token'] === $resetToken) {
                $user['password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT);
                $user['reset_token'] = null;
                return $storage->save('users', $user);
            }
        }
        return false;
    }
}
