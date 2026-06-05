<?php

declare(strict_types=1);

namespace App\Auth;

use App\Storage\StorageManager;

class AuthManager
{
    private Session $session;
    private StorageManager $storage;

    public function __construct(Session $session, StorageManager $storage)
    {
        $this->session = $session;
        $this->storage = $storage;
    }

    public function login(string $username, string $password): bool
    {
        $user = $this->storage->findOne('users', ['username' => $username]);

        if ($user && password_verify($password, $user['password'])) {
            $this->session->set('user_id', $user['id']);
            $this->session->set('username', $user['username']);
            $this->session->set('role', $user['role'] ?? 'user');
            return true;
        }

        return false;
    }

    public function logout(): void
    {
        $this->session->destroy();
    }

    public function isLoggedIn(): bool
    {
        return $this->session->has('user_id');
    }

    public function getCurrentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $this->session->get('user_id'),
            'username' => $this->session->get('username'),
            'role' => $this->session->get('role'),
        ];
    }
}
