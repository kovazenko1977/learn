<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

Auth::requireRole(['superadmin']);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        $settings = Storage::read('settings');
        unset($settings['api_key']); // Hide sensitive key from normal get
        echo json_encode(['success' => true, 'settings' => $settings]);
        break;

    case 'update':
        $data = json_decode(file_get_contents('php://input'), true);
        $settings = Storage::read('settings');

        $new_settings = array_merge($settings, Security::sanitize($data));
        Storage::write('settings', $new_settings);

        Security::log('update_settings', $_SESSION['user_id'], 'settings');
        echo json_encode(['success' => true]);
        break;

    case 'toggle_maintenance':
        $settings = Storage::read('settings');
        $settings['maintenance_mode'] = !($settings['maintenance_mode'] ?? false);
        Storage::write('settings', $settings);
        Security::log('toggle_maintenance', $_SESSION['user_id'], 'settings', ['status' => $settings['maintenance_mode']]);
        echo json_encode(['success' => true, 'maintenance' => $settings['maintenance_mode']]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
