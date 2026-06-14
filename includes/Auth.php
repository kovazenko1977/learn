<?php
require_once __DIR__ . '/TokenProvider.php';

class Auth {
    private $storage;
    private $tokenProvider;
    private $user = null;

    public function __construct($storage, $settings = []) {
        $this->storage = $storage;
        $this->tokenProvider = new TokenProvider($settings);
        $this->checkSession();
    }

    private function checkSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SESSION['token'] ?? null;
        if ($token) {
            if (strpos($token, 'Bearer ') === 0) {
                $token = substr($token, 7);
            }
            $payload = $this->tokenProvider->validate($token);
            if ($payload) {
                $this->user = $payload;
            }
        }
    }

    public function login($username, $password) {
        $users = $this->storage->get('users');
        $user = null;
        foreach ($users as $u) {
            if ($u['username'] === $username && password_verify($password, $u['password_hash'])) {
                $user = $u;
                break;
            }
        }

        if ($user) {
            unset($user['password_hash']);
            $token = $this->tokenProvider->generate($user);
            $_SESSION['token'] = $token;
            return ['token' => $token, 'user' => $user];
        }
        return false;
    }

    public function logout() {
        if (session_status() !== PHP_SESSION_NONE) {
            session_destroy();
        }
        $this->user = null;
    }

    public function getUser() {
        return $this->user;
    }

    public function hasRole($roles) {
        if (!$this->user) return false;
        if (is_string($roles)) $roles = [$roles];
        return in_array($this->user['role'], $roles);
    }

    public function requireRole($roles) {
        if (!$this->hasRole($roles)) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
    }

    public function requireAuth() {
        if (!$this->user) {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
    }
}
