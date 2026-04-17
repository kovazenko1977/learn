<?php
require "includes/Storage.php";
require "includes/Security.php";
require "includes/Auth.php";
require "includes/Logger.php";

Auth::init();
$login = "admin";
$password = "admin123456";

$users = Storage::list('users');
$foundUser = null;
foreach ($users as $u) {
    if ($u['login'] === $login) {
        $foundUser = $u;
        break;
    }
}

if ($foundUser && Auth::login($foundUser, $password)) {
    echo "AUTH_OK\n";
} else {
    echo "AUTH_FAIL\n";
}
