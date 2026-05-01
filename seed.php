<?php
require_once __DIR__ . '/includes/Storage.php';

$storage = new Storage(__DIR__ . '/data/');

// 1. Initial Users
$users = [
    [
        'id' => 'u1',
        'login' => 'admin',
        'password' => password_hash('admin', PASSWORD_DEFAULT),
        'role' => 'admin',
        'name' => 'Администратор Системы',
        'email' => 'admin@example.com',
        'department_id' => 'd1'
    ],
    [
        'id' => 'u2',
        'login' => 'head',
        'password' => password_hash('head', PASSWORD_DEFAULT),
        'role' => 'head',
        'name' => 'Начальник Отдела',
        'email' => 'head@example.com',
        'department_id' => 'd1'
    ],
    [
        'id' => 'u3',
        'login' => 'tech',
        'password' => password_hash('tech', PASSWORD_DEFAULT),
        'role' => 'executor',
        'name' => 'Исполнитель Техник',
        'email' => 'tech@example.com',
        'department_id' => 'd1'
    ],
    [
        'id' => 'u4',
        'login' => 'user',
        'password' => password_hash('user', PASSWORD_DEFAULT),
        'role' => 'employee',
        'name' => 'Сотрудник Заявитель',
        'email' => 'user@example.com',
        'department_id' => 'd1'
    ]
];

foreach ($users as $user) {
    $storage->save('users', $user);
}

// 2. Initial Settings
$settings = [
    'categories' => ['IT', 'Хозяйственные нужды', 'Ремонт мебели', 'Сантехника', 'Электрика'],
    'priorities' => ['Низкий', 'Средний', 'Высокий', 'Критический'],
    'departments' => [
        ['id' => 'd1', 'name' => 'Общий отдел'],
        ['id' => 'd2', 'name' => 'IT отдел'],
        ['id' => 'd3', 'name' => 'Бухгалтерия']
    ],
    'form_config' => [
        [
            'id' => 'f1',
            'label' => 'Детальное описание',
            'type' => 'textarea',
            'required' => true
        ],
        [
            'id' => 'f2',
            'label' => 'Расположение (кабинет)',
            'type' => 'text',
            'required' => false
        ]
    ],
    'sla' => [
        'Низкий' => 48, // hours
        'Средний' => 24,
        'Высокий' => 8,
        'Критический' => 2
    ],
    'storage_mode' => 'json',
    'jwt_secret' => bin2hex(random_bytes(32))
];

foreach ($settings as $key => $value) {
    $storage->save('settings', ['id' => $key, 'value' => $value]);
}

// Special settings file for Storage class itself
file_put_contents(__DIR__ . '/data/config.json', json_encode($settings, JSON_PRETTY_PRINT));

echo "Seed completed successfully!\n";
?>
