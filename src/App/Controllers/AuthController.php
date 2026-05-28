<?php
namespace App\Controllers;

use App\Models\JsonStore;

class AuthController {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('users');
        // Ensure default admin exists
        if (count($this->store->getAll()) === 0) {
            $this->store->add([
                'username' => 'admin',
                'password' => password_hash('admin', PASSWORD_DEFAULT),
                'role' => 'admin'
            ]);
        }
    }

    public function login($data) {
        $users = $this->store->getAll();
        foreach ($users as $user) {
            if ($user['username'] === ($data['username'] ?? '') &&
                password_verify($data['password'] ?? '', $user['password'])) {
                unset($user['password']);
                return [
                    'status' => 'success',
                    'user' => $user,
                    'token' => 'vsprint_token_' . bin2hex(random_bytes(16))
                ];
            }
        }

        return ['status' => 'error', 'message' => 'Неверный логин или пароль'];
    }
}
