<?php

declare(strict_types=1);

/**
 * Smoke Test for Sanatorium 2.0
 */

spl_autoload_register(function ($class) {
    $prefixes = [
        'App\\Modules\\' => 'modules/',
        'App\\' => 'core/',
    ];
    foreach ($prefixes as $prefix => $base_dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) continue;
        $relative_class = substr($class, $len);
        $file = __DIR__ . '/../' . $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) { require $file; return; }
    }
});

use App\Storage\StorageManager;
use App\Storage\JsonDriver;
use App\Utils\RulesEngine;

echo "--- START SMOKE TEST ---\n";

// 1. Storage Test
$driver = new JsonDriver(__DIR__ . '/../storage_test');
$storage = new StorageManager($driver);

$roomId = $storage->insert('rooms', [
    'number' => 'TEST-1',
    'type' => 'Standard',
    'places' => 2,
    'min_age' => 18,
    'status' => 'свободен'
]);
echo "[OK] Room inserted\n";

// 2. Rules Engine Test
$room = $storage->findOne('rooms', ['id' => $roomId]);
$newGuest = ['gender' => 'male', 'age' => 25, 'is_family' => 'no'];
$existingGuests = [['gender' => 'female', 'age' => 30]];

$errors = RulesEngine::validatePlacement($room, $newGuest, $existingGuests);
if (!empty($errors)) {
    echo "[OK] Rules Engine detected gender mismatch: " . $errors[0] . "\n";
} else {
    echo "[FAIL] Rules Engine failed to detect gender mismatch\n";
    exit(1);
}

$childGuest = ['gender' => 'male', 'age' => 10, 'is_family' => 'yes'];
$errors = RulesEngine::validatePlacement($room, $childGuest, []);
if (!empty($errors)) {
    echo "[OK] Rules Engine detected age restriction: " . $errors[0] . "\n";
}

// Cleanup
@unlink(__DIR__ . '/../storage_test/rooms.json');
@rmdir(__DIR__ . '/../storage_test');

echo "--- SMOKE TEST PASSED ---\n";
