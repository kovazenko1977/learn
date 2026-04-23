<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';

Auth::requireAuth();
$currentUser = Auth::getUser();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $clientId = $_GET['client_id'] ?? '';
    $requestId = $_GET['request_id'] ?? '';
    $interactions = Storage::read('interactions');

    if ($clientId) {
        $interactions = array_filter($interactions, function($i) use ($clientId) { return ($i['client_id'] ?? '') === $clientId; });
    } elseif ($requestId) {
        $interactions = array_filter($interactions, function($i) use ($requestId) { return ($i['request_id'] ?? '') === $requestId; });
    }

    echo json_encode(array_reverse(array_values($interactions)));
} elseif ($method === 'POST') {
    $data = Security::sanitize(json_decode(file_get_contents('php://input'), true));
    $interactions = Storage::read('interactions');

    $entry = [
        'id' => uniqid('int_'),
        'client_id' => $data['client_id'] ?? null,
        'request_id' => $data['request_id'] ?? null,
        'user_id' => $currentUser['id'],
        'user_name' => $currentUser['name'],
        'type' => $data['type'] ?? 'note', // note, call, meeting, comment
        'text' => $data['text'],
        'timestamp' => date('Y-m-d H:i:s'), // Normalized key
        'date' => date('Y-m-d H:i:s')
    ];

    $interactions[] = $entry;
    Storage::save('interactions', $interactions);
    Storage::addPoints($currentUser['id'], 2);
    echo json_encode(['success' => true, 'entry' => $entry]);
}
