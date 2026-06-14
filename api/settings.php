<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$settingsFile = __DIR__ . '/../data/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [
    'storage_mode' => 'json',
    'form_fields' => [],
    'categories' => ['IT', 'Ремонт', 'Уборка', 'Хознужды'],
    'priorities' => ['low', 'medium', 'high', 'urgent'],
    'sla_rules' => [
        'low' => 72,
        'medium' => 48,
        'high' => 24,
        'urgent' => 4
    ]
];

$storage = new Storage($settings);
$auth = new Auth($storage, $settings);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $publicSettings = $settings;
    unset($publicSettings['mysql']['pass']);
    unset($publicSettings['JWT_SECRET']);
    unset($publicSettings['telegram_bot_token']);

    echo json_encode($publicSettings);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth->requireRole('admin');
    $input = json_decode(file_get_contents('php://input'), true);

    $settings = array_merge($settings, $input);
    file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo json_encode(['success' => true]);
}
