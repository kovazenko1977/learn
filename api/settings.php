<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$storage = Storage::getInstance();
$currentUser = Auth::validateToken();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// GET Settings - public or authenticated
if ($method === 'GET' && $action === '') {
    $settings = $storage->get('settings');
    $categories = $storage->get('categories');
    $priorities = $storage->get('priorities');

    // Hide sensitive internal settings for non-admins
    if (!$currentUser || $currentUser['role'] !== 'admin') {
        unset($settings['mysql']);
        unset($settings['jwt_secret']);
        unset($settings['notifications']['smtp_user']);
    }

    echo json_encode([
        'success' => true,
        'settings' => $settings,
        'categories' => $categories,
        'priorities' => $priorities
    ]);
    exit;
}

// Admin only actions
if (!$currentUser || $currentUser['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Доступ разрешён только администратору']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if ($method === 'POST' && $action === 'update-settings') {
    $currentSettings = $storage->get('settings');

    if (isset($input['system_title'])) $currentSettings['system_title'] = trim($input['system_title']);
    if (isset($input['company_name'])) $currentSettings['company_name'] = trim($input['company_name']);
    if (isset($input['default_language'])) $currentSettings['default_language'] = trim($input['default_language']);
    if (isset($input['default_theme'])) $currentSettings['default_theme'] = trim($input['default_theme']);
    if (isset($input['sla_warning_threshold_pct'])) $currentSettings['sla_warning_threshold_pct'] = (int)$input['sla_warning_threshold_pct'];
    if (isset($input['notifications'])) $currentSettings['notifications'] = array_merge($currentSettings['notifications'] ?? [], $input['notifications']);

    $filePath = __DIR__ . '/../data/settings.json';
    file_put_contents($filePath, json_encode($currentSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo json_encode(['success' => true, 'message' => 'Настройки успешно сохранены', 'settings' => $currentSettings]);
    exit;
}

if ($method === 'POST' && $action === 'switch-storage') {
    $mode = strtolower(trim($input['storage_mode'] ?? 'json'));
    if ($mode === 'json') {
        $currentSettings = $storage->get('settings');
        $currentSettings['storage_mode'] = 'json';
        file_put_contents(__DIR__ . '/../data/settings.json', json_encode($currentSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(['success' => true, 'message' => 'Переключено на хранилище JSON']);
        exit;
    } elseif ($mode === 'mysql') {
        $dbConfig = $input['mysql'] ?? [];
        $res = $storage->migrateToMySQL($dbConfig);
        if ($res['success']) {
            echo json_encode(['success' => true, 'message' => $res['message']]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $res['error']]);
        }
        exit;
    }
}

if ($method === 'POST' && $action === 'categories') {
    $id = $input['id'] ?? null;
    if ($id) {
        $storage->update('categories', $id, [
            'name' => trim($input['name']),
            'description' => trim($input['description'] ?? '')
        ]);
    } else {
        $storage->insert('categories', [
            'name' => trim($input['name']),
            'code' => trim($input['code'] ?? ('cat_' . time())),
            'description' => trim($input['description'] ?? '')
        ]);
    }
    echo json_encode(['success' => true, 'categories' => $storage->get('categories')]);
    exit;
}

if ($method === 'POST' && $action === 'priorities') {
    $id = $input['id'] ?? null;
    if ($id) {
        $storage->update('priorities', $id, [
            'name' => trim($input['name']),
            'color' => trim($input['color'] ?? '#3B82F6'),
            'sla_hours' => (int)($input['sla_hours'] ?? 24)
        ]);
    } else {
        $storage->insert('priorities', [
            'name' => trim($input['name']),
            'code' => trim($input['code'] ?? ('prio_' . time())),
            'color' => trim($input['color'] ?? '#3B82F6'),
            'sla_hours' => (int)($input['sla_hours'] ?? 24)
        ]);
    }
    echo json_encode(['success' => true, 'priorities' => $storage->get('priorities')]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Недействительное действие']);
