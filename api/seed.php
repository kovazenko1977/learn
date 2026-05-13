<?php
require_once __DIR__ . '/../includes/Storage.php';

header('Content-Type: application/json');

// Security check: only allow seeding if the database is empty or if a specific secret is provided
$users = Storage::read('users');
if (!empty($users) && ($_GET['secret'] ?? '') !== 'seed123') {
    http_response_code(403);
    echo json_encode(['error' => 'System already initialized. Use secret to reset.']);
    exit;
}

$users = [
    [
        'id' => Storage::generateId(),
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'Administrator',
        'name' => 'System Admin',
        'department' => 'IT'
    ],
    [
        'id' => Storage::generateId(),
        'username' => 'head',
        'password' => password_hash('head123', PASSWORD_DEFAULT),
        'role' => 'Head of Department',
        'name' => 'John Doe',
        'department' => 'Maintenance'
    ],
    [
        'id' => Storage::generateId(),
        'username' => 'exec',
        'password' => password_hash('exec123', PASSWORD_DEFAULT),
        'role' => 'Executor',
        'name' => 'Bill Gates',
        'department' => 'Maintenance'
    ]
];

$settings = [
    'categories' => ['Electrical', 'Plumbing', 'Cleaning', 'IT Support', 'General'],
    'priorities' => ['Low', 'Medium', 'High', 'Urgent'],
    'sla' => [
        'Low' => 72,
        'Medium' => 48,
        'High' => 24,
        'Urgent' => 4
    ],
    'form_fields' => [
        ['id' => 'f1', 'label' => 'Room Number', 'type' => 'text'],
        ['id' => 'f2', 'label' => 'Problem Description', 'type' => 'textarea']
    ],
    'jwt_secret' => bin2hex(random_bytes(32))
];

Storage::write('users', $users);
Storage::write('settings', $settings);
Storage::write('tasks', []);

echo json_encode(['success' => true, 'message' => 'System seeded successfully']);
