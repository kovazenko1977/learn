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
    $interactions = Storage::read('interactions');
    if ($clientId) {
        $interactions = array_filter($interactions, function($i) use ($clientId) { return $i['client_id'] === $clientId; });
    }
    echo json_encode(array_reverse(array_values($interactions)));
} elseif ($method === 'POST') {
    $data = Security::sanitize(json_decode(file_get_contents('php://input'), true));
    $interactions = Storage::read('interactions');

    $entry = [
        'id' => uniqid('int_'),
        'client_id' => $data['client_id'],
        'user_id' => $currentUser['id'],
        'user_name' => $currentUser['name'],
        'type' => $data['type'] ?? 'note', // note, call, meeting
        'text' => $data['text'],
        'date' => date('Y-m-d H:i:s')
    ];

    $interactions[] = $entry;
    Storage::save('interactions', $interactions);
    Storage::addPoints($currentUser['id'], 2); // Small reward for activity
    echo json_encode(['success' => true, 'entry' => $entry]);
}
