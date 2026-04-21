<?php
require_once __DIR__ . '/../includes/Storage.php';

header('Content-Type: application/json');

$settings = Storage::read('settings.json');

// Return only public settings
$publicSettings = [
    'bot_name' => $settings['bot_name'],
    'welcome_message' => $settings['welcome_message'],
    'contacts' => $settings['contacts'],
    'directions' => $settings['directions']
];

echo json_encode($publicSettings);
