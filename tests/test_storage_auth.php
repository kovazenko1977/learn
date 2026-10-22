<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

echo "Testing Storage & Auth...\n";

$storage = Storage::getInstance();
echo "Storage mode: " . $storage->getMode() . "\n";

$users = $storage->get('users');
echo "Total users loaded: " . count($users) . "\n";

if (count($users) > 0) {
    $admin = $users[0];
    $token = Auth::generateToken($admin);
    echo "Generated JWT Token: " . substr($token, 0, 30) . "...\n";

    // Validate generated token
    $validatedUser = Auth::validateToken($token);
    if ($validatedUser && $validatedUser['username'] === $admin['username']) {
        echo "Token validation SUCCESS!\n";
    } else {
        echo "Token validation FAILED!\n";
        exit(1);
    }
}
echo "ALL TESTS PASSED.\n";
