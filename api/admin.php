<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

header('Content-Type: application/json');

$user = Auth::checkRole(['admin']);
$storage = new Storage();
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'users') {
    if ($method === 'GET') {
        echo json_encode($storage->getAll('users'));
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['id'])) $data['id'] = 'u_' . uniqid();
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $storage->save('users', $data);
        echo json_encode($data);
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? '';
        $storage->delete('users', $id);
        echo json_encode(['success' => true]);
    }
} elseif ($action === 'settings') {
    if ($method === 'GET') {
        $settings = $storage->getAll('settings');
        $result = [];
        foreach ($settings as $s) {
            $result[$s['id']] = $s['value'];
        }
        echo json_encode($result);
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        foreach ($data as $key => $value) {
            $storage->save('settings', ['id' => $key, 'value' => $value]);

            // Also update the core config.json if it's one of the core settings
            if (in_array($key, ['storage_mode', 'db_config'])) {
                // This would be handled by storage->setMode but we keep it simple here
            }
        }
        echo json_encode(['success' => true]);
    }
} elseif ($action === 'storage_mode') {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $mode = $data['mode'] ?? 'json';
        $dbConfig = $data['db_config'] ?? null;

        $storage->setMode($mode, $dbConfig);
        echo json_encode(['success' => true, 'mode' => $mode]);
    }
} else {
    header('HTTP/1.0 404 Not Found');
    echo json_encode(['error' => 'Action not found']);
}
