<?php
require_once __DIR__ . '/../includes/Storage.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$settings = Storage::read('settings.json');

// Return only public settings
$publicSettings = [
    'bot_name' => $settings['bot_name'] ?? 'Bot',
    'welcome_message' => $settings['welcome_message'] ?? '',
    'contacts' => $settings['contacts'] ?? [],
    'directions' => $settings['directions'] ?? [],
    'visuals' => $settings['visuals'] ?? [],
    'fallback' => $settings['fallback'] ?? [],
    'forms' => $settings['forms'] ?? [],
    'features' => $settings['features'] ?? [],
    'departments' => $settings['departments'] ?? [],
    'quick_start_menu' => $settings['quick_start_menu'] ?? [],
    'enabled' => $settings['enabled'] ?? true
];

echo json_encode($publicSettings);
