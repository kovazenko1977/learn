<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';

$users = [
    [
        "id" => "0000000000000001",
        "username" => "1",
        "password_hash" => password_hash("1", PASSWORD_DEFAULT),
        "full_name" => "Администратор Системы",
        "role" => "Administrator",
        "department" => "IT",
        "created_at" => date('Y-m-d H:i:s')
    ],
    [
        "id" => bin2hex(random_bytes(8)),
        "username" => "head",
        "password_hash" => password_hash("1", PASSWORD_DEFAULT),
        "full_name" => "Иванов Иван (Нач. IT)",
        "role" => "Head of Department",
        "department" => "IT",
        "created_at" => date('Y-m-d H:i:s')
    ],
    [
        "id" => bin2hex(random_bytes(8)),
        "username" => "tech",
        "password_hash" => password_hash("1", PASSWORD_DEFAULT),
        "full_name" => "Петров Петр (Техник)",
        "role" => "Executor",
        "department" => "IT",
        "created_at" => date('Y-m-d H:i:s')
    ],
    [
        "id" => bin2hex(random_bytes(8)),
        "username" => "user",
        "password_hash" => password_hash("1", PASSWORD_DEFAULT),
        "full_name" => "Сидорова Анна",
        "role" => "Responsible Employee",
        "department" => "Бухгалтерия",
        "created_at" => date('Y-m-d H:i:s')
    ]
];

$tasks = [];
$categories = ["Техническая поддержка", "Ремонт оборудования", "Хозяйственные нужды"];
$priorities = ["low", "medium", "high", "critical"];
$statuses = ["New", "Assigned", "In Work", "Completed"];

for ($i = 1; $i <= 15; $i++) {
    $p = $priorities[array_rand($priorities)];
    $createdAt = date('Y-m-d H:i:s', strtotime("-$i days"));
    $tasks[] = [
        "id" => bin2hex(random_bytes(8)),
        "title" => "Тестовая заявка #$i",
        "category" => $categories[array_rand($categories)],
        "priority" => $p,
        "description" => "Описание тестовой проблемы для заявки номер $i. Требуется проверка.",
        "location" => "Кабинет " . rand(100, 500),
        "status" => $statuses[array_rand($statuses)],
        "created_by" => $users[3]['id'],
        "created_by_name" => $users[3]['full_name'],
        "department" => "IT",
        "executor_id" => $users[2]['id'],
        "executor_name" => $users[2]['full_name'],
        "created_at" => $createdAt,
        "deadline" => date('Y-m-d H:i:s', strtotime($createdAt . " +24 hours")),
        "comments" => [],
        "attachments" => []
    ];
}

Storage::saveData('users', $users);
Storage::saveData('tasks', $tasks);

echo json_encode(['success' => true, 'message' => 'Система успешно инициализирована демо-данными']);