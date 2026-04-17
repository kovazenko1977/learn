<?php
require "includes/Storage.php";
require "includes/Security.php";
require "includes/Auth.php";
require "includes/Logger.php";

Auth::init();
$_SESSION = [];

$login = "admin";
$password = "admin123456";

$users = Storage::list("users");
$foundUser = null;
foreach ($users as $u) {
    if ($u['login'] === $login) {
        $foundUser = $u;
        break;
    }
}

if ($foundUser && Auth::login($foundUser, $password)) {
    echo "Login successful\n";
    print_r($_SESSION);
} else {
    echo "Login failed\n";
    if ($foundUser) {
        echo "User found: " . $foundUser['login'] . "\n";
        echo "Hash: " . $foundUser['password_hash'] . "\n";
        if (password_verify($password, $foundUser['password_hash'])) {
            echo "Verification OK manually\n";
        } else {
            echo "Verification FAILED manually\n";
        }
    } else {
        echo "User not found\n";
    }
}
