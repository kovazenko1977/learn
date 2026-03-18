<?php
require_once '../includes/Auth.php';
require_once '../includes/Storage.php';

header('Content-Type: application/json');

if (!Auth::isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    echo json_encode(Storage::read('clients'));
} elseif ($method === 'POST') {
    $clients = Storage::read('clients');
    $data = json_decode(file_get_contents('php://input'), true);

    $id = !empty($data['id']) ? $data['id'] : uniqid();

    // Check if password needs hashing (if it's new or changed)
    $password = $data['password'];
    if (strpos($password, '$2y$') !== 0) {
        $password = password_hash($password, PASSWORD_BCRYPT);
    }

    $client = [
        'id' => $id,
        'username' => $data['username'],
        'password' => $password,
        'name' => $data['name'],
        'details' => $data['details']
    ];

    $found = false;
    foreach ($clients as &$c) {
        if ($c['id'] === $id) {
            $c = $client;
            $found = true;
            break;
        }
    }
    if (!$found) $clients[] = $client;

    Storage::write('clients', $clients);
    echo json_encode(['success' => true]);
} elseif ($method === 'DELETE') {
    $id = $_GET['id'];
    $clients = Storage::read('clients');
    $clients = array_values(array_filter($clients, fn($c) => $c['id'] !== $id));
    Storage::write('clients', $clients);
    echo json_encode(['success' => true]);
}
