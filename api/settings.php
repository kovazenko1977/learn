<?php
Auth::requireRole(['admin']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $type = isset($_GET['type']) ? $_GET['type'] : 'system';
    $settings = Storage::read('settings', $type);
    if (!$settings) {
        if ($type === 'modules') {
            $settings = [
                'finance' => true,
                'tasks' => true,
                'sources' => true,
                'tags' => true,
                'chat' => true
            ];
        } else {
            $settings = [
                'clinic_name' => 'Dental CRM',
                'timezone' => 'UTC'
            ];
        }
    }
    echo json_encode($settings);
} elseif ($method === 'POST') {
    $type = isset($_GET['type']) ? $_GET['type'] : 'system';
    $input = json_decode(file_get_contents('php://input'), true);
    $input = Security::sanitize($input);
    Storage::write('settings', $type, $input);
    Logger::log("Settings updated: $type", "info", "system.log");
    echo json_encode(['success' => true]);
}
