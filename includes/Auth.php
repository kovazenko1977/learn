<?php

require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/Functions.php';

class Auth {
    // User Auth
    public static function registerUser(string $phone, string $password, string $fullName = ''): array {
        $phone = normalizePhone($phone);
        if (empty($phone) || strlen($phone) < 5) {
            return ['success' => false, 'error' => 'Укажите корректный номер телефона'];
        }
        if (strlen($password) < 4) {
            return ['success' => false, 'error' => 'Пароль должен содержать минимум 4 символа'];
        }

        $pdo = Storage::getPDO();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Пользователь с таким номером телефона уже зарегистрирован'];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (phone, password_hash, full_name) VALUES (?, ?, ?)");
        $stmt->execute([$phone, $passwordHash, $fullName]);
        $userId = $pdo->lastInsertId();

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_phone'] = $phone;

        return [
            'success' => true,
            'user' => [
                'id' => $userId,
                'phone' => $phone,
                'full_name' => $fullName
            ]
        ];
    }

    public static function loginUser(string $phone, string $password): array {
        $phone = normalizePhone($phone);
        $pdo = Storage::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Неверный номер телефона или пароль'];
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_phone'] = $user['phone'];

        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'phone' => $user['phone'],
                'full_name' => $user['full_name']
            ]
        ];
    }

    public static function getCurrentUser(): ?array {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        $pdo = Storage::getPDO();
        $stmt = $pdo->prepare("SELECT id, phone, full_name, created_at FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function logoutUser(): void {
        unset($_SESSION['user_id'], $_SESSION['user_phone']);
    }

    // Admin Auth
    public static function loginAdmin(string $login, string $password): array {
        $pdo = Storage::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE login = ?");
        $stmt->execute([trim($login)]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            return ['success' => false, 'error' => 'Неверный логин или пароль администратора'];
        }

        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_login'] = $admin['login'];

        return [
            'success' => true,
            'admin' => [
                'id' => $admin['id'],
                'login' => $admin['login']
            ]
        ];
    }

    public static function isAdmin(): bool {
        return !empty($_SESSION['admin_id']);
    }

    public static function logoutAdmin(): void {
        unset($_SESSION['admin_id'], $_SESSION['admin_login']);
    }

    public static function updateAdminCredentials(string $newLogin, string $newPassword): array {
        if (!self::isAdmin()) {
            return ['success' => false, 'error' => 'Доступ запрещен'];
        }

        $newLogin = trim($newLogin);
        if (empty($newLogin)) {
            return ['success' => false, 'error' => 'Логин не может быть пустым'];
        }

        $pdo = Storage::getPDO();
        // Check if another admin user has this login
        $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE login = ? AND id != ?");
        $stmt->execute([$newLogin, $_SESSION['admin_id']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Этот логин уже используется'];
        }

        if (!empty($newPassword)) {
            if (strlen($newPassword) < 4) {
                return ['success' => false, 'error' => 'Пароль должен быть не менее 4 символов'];
            }
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin_users SET login = ?, password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$newLogin, $hash, $_SESSION['admin_id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE admin_users SET login = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$newLogin, $_SESSION['admin_id']]);
        }

        $_SESSION['admin_login'] = $newLogin;

        return ['success' => true, 'message' => 'Данные администратора успешно обновлены'];
    }
}
