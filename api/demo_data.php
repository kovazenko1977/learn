<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

Auth::requireAdmin();

$departments = [
    ['id' => 1, 'name' => 'Сантехника', 'description' => 'Обслуживание водопровода и канализации'],
    ['id' => 2, 'name' => 'Электрика', 'description' => 'Электросети и освещение'],
    ['id' => 3, 'name' => 'Оборудование', 'description' => 'Производственное оборудование'],
    ['id' => 4, 'name' => 'Мебель', 'description' => 'Ремонт и сборка мебели']
];

$users = Storage::read('users.json');
// Add some heads and executors
$demoUsers = [
    ['id' => 2, 'username' => 'plumber_head', 'password' => password_hash('1', PASSWORD_DEFAULT), 'role' => 'head', 'full_name' => 'Иванов И.И. (Сантехника)', 'department_id' => 1],
    ['id' => 3, 'username' => 'electric_head', 'password' => password_hash('1', PASSWORD_DEFAULT), 'role' => 'head', 'full_name' => 'Петров П.П. (Электрика)', 'department_id' => 2],
    ['id' => 4, 'username' => 'executor1', 'password' => password_hash('1', PASSWORD_DEFAULT), 'role' => 'executor', 'full_name' => 'Сидоров С.С.', 'department_id' => 1],
    ['id' => 5, 'username' => 'employee1', 'password' => password_hash('1', PASSWORD_DEFAULT), 'role' => 'employee', 'full_name' => 'Алексеев А.А.']
];

foreach ($demoUsers as $du) {
    Storage::saveItem('users.json', $du);
}

$settings = Storage::read('settings.json');
$settings['departments'] = $departments;
Storage::write('settings.json', $settings);

$tasks = [];
$priorities = ['low', 'medium', 'high'];
$statuses = ['new', 'assigned', 'completed'];

for ($i = 1; $i <= 50; $i++) {
    $deptId = rand(1, 4);
    $priority = $priorities[rand(0, 2)];
    $status = $statuses[rand(0, 2)];
    $createdAt = date('Y-m-d H:i:s', strtotime("-" . rand(0, 30) . " days " . rand(0, 23) . " hours"));

    $tasks[] = [
        'id' => 1000 + $i,
        'department_id' => $deptId,
        'priority' => $priority,
        'status' => $status,
        'description' => "Демонстрационная заявка #$i для отдела " . $departments[$deptId-1]['name'],
        'created_at' => $createdAt,
        'created_by' => 5,
        'deadline' => date('Y-m-d H:i:s', strtotime($createdAt . " + 2 days")),
        'executor_id' => ($status !== 'new') ? 4 : null,
        'history' => [
            ['at' => $createdAt, 'msg' => 'Заявка создана', 'user' => 'Алексеев А.А.']
        ]
    ];
}

Storage::write('tasks.json', $tasks);

echo json_encode(['success' => true]);
