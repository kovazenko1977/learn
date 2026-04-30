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
    }

    // DISABLE AUTH BY DEFAULT
    $settingsFile = $dataDir . '/settings.json';
    $settings = [['id' => 'global', 'auth_enabled' => false]];
    file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    chmod($settingsFile, 0644);
    echo "Disabled global authentication. You can now enter without a password.\n";

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

    echo "\nSystem fix complete. Try refreshing index.php now.\n";
}

fix_system();
