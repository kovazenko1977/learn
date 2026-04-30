<?php
header('Content-Type: text/plain; charset=utf-8');

function fix_system() {
    $dataDir = __DIR__ . '/data';
    if (!file_exists($dataDir)) {
        if (!mkdir($dataDir, 0755, true)) {
            die("FAILED to create data directory.\n");
        }
        echo "Created data directory.\n";
    }

    chmod($dataDir, 0755);
    echo "Set data directory permissions to 0755.\n";

    $usersFile = $dataDir . '/users.json';

    // Explicitly using '1' / '1' as requested
    $hashedPassword = password_hash('1', PASSWORD_DEFAULT);

    $users = [
        [
            'id' => 1,
            'login' => '1',
            'password_hash' => $hashedPassword,
            'full_name' => 'Администратор (1)',
            'role' => 'admin',
            'department_id' => null,
            'is_active' => 1,
            'created_at' => date('c')
        ]
    ];

    if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        chmod($usersFile, 0644);
        echo "Re-initialized users.json with login '1' and password '1'.\n";
        echo "Login: 1\n";
        echo "Password: 1\n";
        echo "Verify hash manually: " . (password_verify('1', $hashedPassword) ? "YES" : "NO") . "\n";
    } else {
        echo "FAILED to write users.json. Check directory ownership/permissions.\n";
    }

    // Ensure other collections exist
    $files = ['requests.json', 'status_history.json', 'departments.json', 'work_types.json'];
    foreach ($files as $f) {
        $path = $dataDir . '/' . $f;
        if (!file_exists($path)) {
            file_put_contents($path, '[]');
            chmod($path, 0644);
            echo "Created empty collection: $f\n";
        }
    }

    echo "\nSystem fix complete. Try logging in now at index.php\n";
}

fix_system();
