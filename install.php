<?php
/**
 * Flower Studio Pro - Installer
 */

// Ensure data, uploads, icons directories exist with permissions
$dirs = [__DIR__ . '/data', __DIR__ . '/uploads', __DIR__ . '/icons', __DIR__ . '/data/backups'];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    } else {
        @chmod($dir, 0777);
    }
}

// Initial Data Files
$defaultUsers = [
    [
        "id" => 1,
        "name" => "Администратор",
        "phone" => "1111",
        "password" => '$2y$10$wT8K8U1pL.k5e8wN3vL7e.N9u0/mK9k2a4J1y9Y5A3s9E2L5d1C6O', // 1111
        "role" => "admin",
        "is_blocked" => false,
        "created_at" => date("Y-m-d H:i:s")
    ]
];

$defaultSettings = [
    [
        "id" => 1,
        "store_name" => "Flower Studio Pro",
        "store_phone" => "+375 29 111-22-33",
        "store_address" => "г. Минск, пр. Независимости, 10",
        "working_hours" => "08:00 - 22:00 ежедневно",
        "currency" => "BYN",
        "delivery_price" => 10,
        "free_delivery_from" => 100,
        "min_order" => 30,
        "auto_reply" => "Спасибо за обращение! Менеджер ответит вам в ближайшее время.",
        "instagram" => "https://instagram.com",
        "telegram" => "https://t.me",
        "viber" => "viber://chat"
    ]
];

$defaultProducts = [
    [
        "id" => 1,
        "title" => "Розовое Облако",
        "description" => "Нежнейший букет из премиальных пионовидных роз и эвкалипта",
        "price" => 115.00,
        "old_price" => 140.00,
        "category" => "Букеты",
        "stock" => 4,
        "emoji" => "🌸",
        "image" => "",
        "is_sale" => true,
        "active" => true,
        "created_at" => date("Y-m-d H:i:s")
    ],
    [
        "id" => 2,
        "title" => "Алые Чувства",
        "description" => "Страстная композиция из 25 редких эквадорских роз",
        "price" => 160.00,
        "old_price" => null,
        "category" => "Розы",
        "stock" => 8,
        "emoji" => "🌹",
        "image" => "",
        "is_sale" => false,
        "active" => true,
        "created_at" => date("Y-m-d H:i:s")
    ]
];

$defaultPromos = [
    [
        "id" => 1,
        "title" => "Скидка 15% на первый заказ!",
        "text" => "Используйте промокод FIRST15 при оформлении заказа",
        "created_at" => date("Y-m-d H:i:s")
    ]
];

$defaultPromocodes = [
    [
        "id" => 1,
        "code" => "FIRST15",
        "discount" => 15,
        "used" => 0,
        "limit" => 100,
        "created_at" => date("Y-m-d H:i:s")
    ]
];

if (!file_exists(__DIR__ . '/data/users.json')) {
    file_put_contents(__DIR__ . '/data/users.json', json_encode($defaultUsers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
if (!file_exists(__DIR__ . '/data/settings.json')) {
    file_put_contents(__DIR__ . '/data/settings.json', json_encode($defaultSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
if (!file_exists(__DIR__ . '/data/products.json')) {
    file_put_contents(__DIR__ . '/data/products.json', json_encode($defaultProducts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
if (!file_exists(__DIR__ . '/data/promos.json')) {
    file_put_contents(__DIR__ . '/data/promos.json', json_encode($defaultPromos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
if (!file_exists(__DIR__ . '/data/promocodes.json')) {
    file_put_contents(__DIR__ . '/data/promocodes.json', json_encode($defaultPromocodes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Self-delete installer
@unlink(__FILE__);

header('Location: /');
exit;
