<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

$settings = Storage::read('settings');
$valid_key = $settings['api_key'] ?? 'EXTERNAL_1C_SECRET_2024';

$api_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';

if ($api_key !== $valid_key) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'sync_users':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            echo json_encode(['error' => 'Invalid data format']);
            break;
        }
        foreach ($data as $u) {
            $existing = Storage::findOne('users', ['username' => $u['username']]);
            if ($existing) {
                Storage::update('users', $existing['id'], $u);
            } else {
                if (empty($u['password'])) $u['password'] = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
                Storage::insert('users', $u);
            }
        }
        Security::log('api_sync_users', 'system', '1c_sync', ['count' => count($data)]);
        echo json_encode(['success' => true, 'synced' => count($data)]);
        break;

    case 'sync_docs':
        // Implementation for 1C to push document metadata or trigger pulls
        $data = json_decode(file_get_contents('php://input'), true);
        Security::log('api_sync_docs', 'system', '1c_sync', ['count' => count($data)]);
        echo json_encode(['success' => true, 'synced' => count($data)]);
        break;

    case 'get_stats':
        $logs = Storage::read('logs');
        $downloads = array_filter($logs, fn($l) => $l['type'] === 'download_document');
        echo json_encode(['success' => true, 'total_downloads' => count($downloads)]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
