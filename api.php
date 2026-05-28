<?php
/**
 * LabelPro Web Professional - Core API
 * (c) 2024 Starovoitov Tools Pro
 */

session_start();
header('Content-Type: application/json');

$config = [
    'admin_pass' => 'admin123', // В реальном проекте использовать хеш
    'data_dir' => __DIR__ . '/data'
];

if (!is_dir($config['data_dir'])) mkdir($config['data_dir'], 0755, true);

$action = $_GET['action'] ?? '';

// Auth Check (Very basic for MVP)
function checkAuth() {
    return isset($_SESSION['auth']) && $_SESSION['auth'] === true;
}

if ($action === 'login') {
    $pass = $_POST['password'] ?? '';
    if ($pass === $config['admin_pass']) {
        $_SESSION['auth'] = true;
        echo json_encode(['status' => 'ok']);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid password']);
    }
    exit;
}

if (!checkAuth() && $action !== '') {
    // Для демонстрации пока разрешим, но в проде - 401
    // http_response_code(401); exit;
}

// Data Handling
function getStore($name) {
    global $config;
    $file = $config['data_dir'] . "/$name.json";
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

function saveStore($name, $data) {
    global $config;
    $file = $config['data_dir'] . "/$name.json";
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

switch ($action) {
    case 'get_products':
        echo json_encode(array_values(getStore('products')));
        break;
    case 'save_product':
        $items = getStore('products');
        $new = json_decode(file_get_contents('php://input'), true);
        if (!isset($new['id'])) $new['id'] = uniqid();
        $items[$new['id']] = $new;
        saveStore('products', $items);
        echo json_encode($new);
        break;
    case 'get_templates':
        echo json_encode(array_values(getStore('templates')));
        break;
    case 'save_template':
        $items = getStore('templates');
        $new = json_decode(file_get_contents('php://input'), true);
        if (!isset($new['id'])) $new['id'] = uniqid();
        $items[$new['id']] = $new;
        saveStore('templates', $items);
        echo json_encode($new);
        break;

    case 'import_csv':
        if (!isset($_FILES['file'])) { echo json_encode(['error' => 'No file']); exit; }
        $handle = fopen($_FILES['file']['tmp_name'], "r");
        $items = getStore('products');
        $headers = fgetcsv($handle, 1000, ",");
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $item = array_combine($headers, $data);
            $id = uniqid();
            $item['id'] = $id;
            $items[$id] = $item;
        }
        fclose($handle);
        saveStore('products', $items);
        echo json_encode(['status' => 'ok', 'count' => count($items)]);
        break;

    case 'get_history':
        echo json_encode(getStore('history'));
        break;

    case 'log_print':
        $history = getStore('history');
        $log = json_decode(file_get_contents('php://input'), true);
        $log['date'] = date('Y-m-d H:i:s');
        $history[] = $log;
        saveStore('history', array_slice($history, -100)); // Keep last 100
        echo json_encode(['status' => 'ok']);
        break;

    default:
        echo json_encode(['status' => 'ready', 'version' => '2.0.0']);
}
