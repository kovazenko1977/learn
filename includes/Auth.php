<?php
session_start();
require_once 'Storage.php';

class Auth {
    private $storage;

    public function __construct() {
        $this->storage = new Storage('users.json');
    }

    public function login($passcode) {
        if (!preg_match('/^\d{6}$/', $passcode)) {
            return false;
        }

        $users = $this->storage->read();
        // For this app, let's say anyone with a valid 6-digit code can enter,
        // and we'll identify them by their code for simplicity, or we can have pre-defined codes.
        // Let's create a user if not exists or just validate.

        $userFound = null;
        foreach ($users as $user) {
            if ($user['passcode'] === $passcode) {
                $userFound = $user;
                break;
            }
        }

        if (!$userFound) {
            // In a real app we might not auto-create, but for this task, let's allow it or have a default.
            // Let's assume we have a few users or just auto-register the first time a code is used.
            $userFound = [
                'id' => uniqid(),
                'passcode' => $passcode,
                'username' => 'User_' . substr($passcode, -3),
                'avatar' => 'assets/img/default-avatar.png'
            ];
            $users[] = $userFound;
            $this->storage->write($users);
        }

        $_SESSION['user'] = $userFound;
        return true;
    }

    public static function check() {
        return isset($_SESSION['user']);
    }

    public static function user() {
        return $_SESSION['user'] ?? null;
    }

    public function logout() {
        session_destroy();
    }
}
