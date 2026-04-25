<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Some settings might be public (e.g. form fields for index.php)
    $settings = Storage::read('settings.json');

    $isAdmin = Auth::authenticate() && Auth::isAdmin();
    $settings['is_admin'] = $isAdmin;

    // Filter sensitive info if not admin
    if (!$isAdmin) {
        unset($settings['admin_password']);
        unset($settings['admin_password_hash']);
        unset($settings['JWT_SECRET']);
        unset($settings['telegram_bot_token']);
    }

    echo json_encode($settings);
    exit;
}

if ($method === 'POST') {
    Auth::requireAdmin();
    $data = json_decode(file_get_contents('php://input'), true);
    $settings = Storage::read('settings.json');

    if (isset($data['action']) && $data['action'] === 'save_template') {
        if (!isset($settings['form_templates'])) $settings['form_templates'] = [];
        $settings['form_templates'][] = [
            'id' => uniqid(),
            'name' => $data['name'],
            'fields' => $data['fields']
        ];
        Storage::write('settings.json', $settings);
        echo json_encode(['success' => true]);
        exit;
    }

    $newSettings = array_merge($settings, $data);
    Storage::write('settings.json', $newSettings);
    echo json_encode(['success' => true]);
    exit;
}
