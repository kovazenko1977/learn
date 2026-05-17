<?php

namespace App\Helpers;

use App\Database\JsonStore;

class Auth
{
    private static ?JsonStore $configStore = null;

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

    public static function login(string $pin): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $config = self::getConfigStore()->getAll();
        if (password_verify($pin, $config['pin'])) {
            $_SESSION['logged_in'] = true;
            return true;
        }
        return false;
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
    }

    public static function check(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $dir = dirname($scriptName);

            // Если мы находимся в папке admin, то login.php находится уровнем выше
            if (basename($dir) === 'admin') {
                $loginUrl = '../login.php';
            } else {
                $loginUrl = 'login.php';
            }

            header('Location: ' . $loginUrl);
            exit;
        }
    }

    public static function updatePin(string $newPin): void
    {
        self::getConfigStore()->set(['pin' => password_hash($newPin, PASSWORD_BCRYPT)]);
    }
}
