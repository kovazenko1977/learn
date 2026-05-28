<?php
namespace App\Controllers;

use App\Models\Database;

class AuthController {
    public function login($data) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$data['username']]);
        $user = $stmt->fetch();

        if ($user && password_verify($data['password'], $user['password'])) {
            unset($user['password']);
            return ['status' => 'success', 'user' => $user, 'token' => 'dummy-jwt-token'];
        }

        return ['status' => 'error', 'message' => 'Неверный логин или пароль'];
    }
}
