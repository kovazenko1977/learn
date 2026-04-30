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

    // Explicitly using 'admin' / 'admin'
    $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);

    $users = [
        [
            'id' => 1,
            'login' => 'admin',
            'password_hash' => $hashedPassword,
            'full_name' => 'Администратор (Системный)',
            'role' => 'admin',
            'department_id' => null,
            'is_active' => 1,
            'created_at' => date('c')
        ]
    ];

    if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        chmod($usersFile, 0644);
        echo "Re-initialized users.json with admin/admin.\n";
        echo "Login: admin\n";
        echo "Password: admin\n";
        echo "Verify hash manually: " . (password_verify('admin', $hashedPassword) ? "YES" : "NO") . "\n";
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
