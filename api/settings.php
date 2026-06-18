<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$settingsFile = __DIR__ . '/../data/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [
    'categories' => ['Сантехника', 'Электрика', 'Мебель', 'Оборудование'],
    'priorities' => ['low', 'medium', 'high', 'urgent'],
    'storage_mode' => 'json',
    'sla_rules' => [
        'low' => 72,
        'medium' => 48,
        'high' => 24,
        'urgent' => 4
    ],
    'form_fields' => []
];

$storage = new Storage($settings);
$auth = new Auth($storage, $settings);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode($settings);
} elseif ($method === 'POST') {
    $auth->requireRole('admin');
    $input = json_decode(file_get_contents('php://input'), true);

    // Filter sensitive data or perform validation if necessary
    $newSettings = array_merge($settings, $input);

    if (file_put_contents($settingsFile, json_encode($newSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        echo json_encode(['success' => true]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Could not save settings']);
    }
}
