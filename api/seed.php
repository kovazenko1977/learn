<?php
require_once __DIR__ . '/../includes/Storage.php';

$users = Storage::read('users');
if (empty($users)) {
    $admin = [
        'id' => Storage::generateId(),
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'Administrator',
        'name' => 'System Administrator'
    ];
    Storage::write('users', [$admin]);
    echo "Default admin created: admin / admin123\n";
} else {
    echo "Users already exist.\n";
}

$settings = Storage::read('settings');
if (empty($settings)) {
    $defaultSettings = [
        'categories' => ['IT Support', 'Maintenance', 'General'],
        'priorities' => ['Low', 'Medium', 'High', 'Urgent'],
        'statuses' => ['New', 'Assigned', 'In Work', 'Completed', 'Rejected'],
        'form_fields' => [
            ['id' => 'desc', 'label' => 'Description', 'type' => 'textarea', 'required' => true]
        ]
    ];
    Storage::write('settings', $defaultSettings);
    echo "Default settings created.\n";
}
