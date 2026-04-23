<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';

Auth::requireAuth();
$currentUser = Auth::getUser();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    $clients = Storage::read('clients');
    echo json_encode($clients);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $data = Security::sanitize($data);
    $clients = Storage::read('clients');

    if (isset($data['id'])) {
        // Update
        foreach ($clients as &$client) {
            if ($client['id'] === $data['id']) {
                $client = array_merge($client, $data);
                $client['updated_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
        Storage::log("Updated client: " . ($data['name'] ?? $data['id']));
    } else {
        // Create
        $data['id'] = uniqid();
        $data['created_at'] = date('Y-m-d H:i:s');
        $clients[] = $data;
        Storage::log("Created client: " . ($data['name'] ?? $data['id']), $currentUser['id']);
        Storage::addPoints($currentUser['id'], 5);
    }

    Storage::save('clients', $clients);
    echo json_encode(['success' => true, 'client' => $data]);
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    $clients = Storage::read('clients');
    $clients = array_filter($clients, function($c) use ($id) {
        return $c['id'] !== $id;
    });
    Storage::save('clients', array_values($clients));
    Storage::log("Deleted client ID: $id");
    echo json_encode(['success' => true]);
}
