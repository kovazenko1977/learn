<?php
require_once 'includes/Storage.php';
$storage = new Storage('data');

$users = [
    [
        'id' => 1,
        'login' => 'admin',
        'password_hash' => password_hash('admin', PASSWORD_DEFAULT),
        'full_name' => 'Администратор',
        'role' => 'admin',
        'department_id' => null,
        'is_active' => 1,
        'created_at' => date('c')
    ]
];
$storage->writeCollection('users', $users);

$depts = [
    ['id' => 1, 'name' => 'Хозяйственный', 'description' => 'Общий отдел', 'manager_id' => null]
];
$storage->writeCollection('departments', $depts);

$wt = [
    ['id' => 1, 'name' => 'Мебель', 'department_id' => 1, 'sla_hours' => 24]
];
$storage->writeCollection('work_types', $wt);

$storage->writeCollection('requests', []);
$storage->writeCollection('status_history', []);

echo "Seeded successfully\n";
