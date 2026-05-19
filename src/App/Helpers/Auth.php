<?php

namespace App\Helpers;

use App\Database\JsonStore;

class Auth
{
    private static ?JsonStore $configStore = null;
    private static ?JsonStore $userStore = null;
    private static ?JsonStore $logStore = null;

    public static function getConfigStore(): JsonStore
    {
        if (self::$configStore === null) {
            self::$configStore = new JsonStore(__DIR__ . '/../../../data/config.json');
            $config = self::$configStore->getAll();
            if (!isset($config['pin'])) {
                self::$configStore->set(['pin' => password_hash('123456', PASSWORD_BCRYPT)]);
            }
        }
        return self::$configStore;
    }

    public static function getUserStore(): JsonStore
    {
        if (self::$userStore === null) {
            self::$userStore = new JsonStore(__DIR__ . '/../../../data/users.json');
            $users = self::$userStore->getAll();
            if (empty($users)) {
                // Default admin user with the default PIN
                self::$userStore->set([[
                    'id' => 'admin_1',
                    'username' => 'admin',
                    'pin' => password_hash('123456', PASSWORD_BCRYPT),
                    'role' => 'admin',
                    'created_at' => date('Y-m-d H:i:s')
                ]]);
            }
        }
        return self::$userStore;
    }

    public static function getLogStore(): JsonStore
    {
        if (self::$logStore === null) {
            self::$logStore = new JsonStore(__DIR__ . '/../../../data/audit.json');
        }
        return self::$logStore;
    }

    public static function log(string $action, ?string $user = null): void
    {
        $log = [
            'id' => uniqid(),
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => $user ?: ($_SESSION['username'] ?? 'System'),
            'action' => $action,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI'
        ];
        $store = self::getLogStore();
        $logs = $store->getAll();
        array_unshift($logs, $log);
        $store->set(array_slice($logs, 0, 1000)); // Keep last 1000 logs
    }

    public static function isLocked(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $attemptsStore = new JsonStore(__DIR__ . '/../../../data/attempts.json');
        $attempts = $attemptsStore->getAll();

        if (isset($attempts[$ip])) {
            $data = $attempts[$ip];
            if ($data['count'] >= 5) {
                $lockTime = 15 * 60; // 15 minutes
                if (time() - $data['last_attempt'] < $lockTime) {
                    return true;
                } else {
                    // Reset after timeout
                    unset($attempts[$ip]);
                    $attemptsStore->set($attempts);
                }
            }
        }
        return false;
    }

    private static function recordAttempt(bool $success): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $attemptsStore = new JsonStore(__DIR__ . '/../../../data/attempts.json');
        $attempts = $attemptsStore->getAll();

        if ($success) {
            unset($attempts[$ip]);
        } else {
            if (!isset($attempts[$ip])) {
                $attempts[$ip] = ['count' => 0, 'last_attempt' => 0];
            }
            $attempts[$ip]['count']++;
            $attempts[$ip]['last_attempt'] = time();
        }
        $attemptsStore->set($attempts);
    }

    public static function login(string $pin): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (self::isLocked()) {
            return false;
        }

        $users = self::getUserStore()->getAll();
        foreach ($users as $user) {
            if (password_verify($pin, $user['pin'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                self::recordAttempt(true);
                self::log("Вход в систему", $user['username']);
                return true;
            }
        }

        self::recordAttempt(false);
        self::log("Неудачная попытка входа", 'Guest');
        return false;
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        self::log("Выход из системы");
        session_destroy();
    }

    public static function check(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public static function isAdmin(): bool
    {
        return self::check() && ($_SESSION['role'] ?? '') === 'admin';
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $dir = rtrim(dirname($scriptName), '/\\');

            // Handle root or subfolder
            if (basename($dir) === 'admin' || basename($dir) === 'api') {
                $loginUrl = '../login.php';
            } else {
                $loginUrl = 'login.php';
            }

            header('Location: ' . $loginUrl);
            exit;
        }
    }

    public static function updatePin(string $newPin, ?string $userId = null): void
    {
        $userId = $userId ?: ($_SESSION['user_id'] ?? null);
        if (!$userId) return;

        $store = self::getUserStore();
        $users = $store->getAll();
        foreach ($users as &$user) {
            if ($user['id'] === $userId) {
                $user['pin'] = password_hash($newPin, PASSWORD_BCRYPT);
                break;
            }
        }
        $store->set($users);
        self::log("Обновление PIN-кода");
    }
}
