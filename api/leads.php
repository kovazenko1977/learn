<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';

Auth::requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(Storage::read('leads'));
} elseif ($method === 'POST') {
    $data = Security::sanitize(json_decode(file_get_contents('php://input'), true));
    $leads = Storage::read('leads');

    if (isset($data['id'])) {
        foreach ($leads as &$lead) {
            if ($lead['id'] === $data['id']) {
                $lead = array_merge($lead, $data);
                break;
            }
        }
    } else {
        $data['id'] = uniqid();
        $data['created_at'] = date('Y-m-d H:i:s');
        $leads[] = $data;
    }

    Storage::save('leads', $leads);
    echo json_encode(['success' => true]);
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    $leads = array_filter(Storage::read('leads'), function($l) use ($id) { return $l['id'] !== $id; });
    Storage::save('leads', array_values($leads));
    echo json_encode(['success' => true]);
}
