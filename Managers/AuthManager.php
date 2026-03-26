<?php
namespace Managers;
use Core\JsonStore;

class AuthManager {
    private $userStore;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $this->userStore = new JsonStore('users');
    }

    public function login($username, $password) {
        $users = $this->userStore->findAll();
        foreach ($users as $user) {
            if ($user['username'] === $username && password_verify($password, $user['password'])) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'name' => $user['name'],
                    'permissions' => $user['permissions']
                ];
                return true;
            }
        }
        return false;
    }

    public function logout() {
        if (isset($_SESSION['user'])) {
            unset($_SESSION['user']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            // session_destroy();
        }
    }

    public function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }

    public function isAuthenticated() {
        return isset($_SESSION['user']);
    }

    public function hasPermission($permission) {
        $user = $this->getCurrentUser();
        if (!$user) return false;
        if (in_array('*', $user['permissions'])) return true;
        return in_array($permission, $user['permissions']);
    }
}
