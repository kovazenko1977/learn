<?php
namespace Managers;

class AuthManager {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login($username, $password) {
        // Демо-данные для входа
        if (($username === 'admin' && $password === 'admin123') ||
            ($username === 'client' && $password === 'client123')) {
            $_SESSION['user'] = [
                'username' => $username,
                'role' => $username === 'admin' ? 'admin' : 'client',
                'id' => uniqid()
            ];
            return true;
        }
        return false;
    }

    public function logout() {
        unset($_SESSION['user']);
        session_destroy();
    }

    public function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }

    public function isAuthenticated() {
        return isset($_SESSION['user']);
    }
}
