<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/SLAProvider.php';
require_once __DIR__ . '/../includes/Security.php';

Auth::requireAuth();
$currentUser = Auth::getUser();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $requests = Storage::read('requests');

    // Filtering based on roles
    if ($currentUser['role'] === 'executor') {
        $requests = array_filter($requests, function($r) use ($currentUser) {
            return ($r['executor_id'] ?? '') === $currentUser['id'];
        });
    } elseif ($currentUser['role'] === 'responsible') {
        $requests = array_filter($requests, function($r) use ($currentUser) {
            return ($r['creator_id'] ?? '') === $currentUser['id'];
        });
    }

    echo json_encode(array_values($requests));

} elseif ($method === 'POST') {
    $data = Security::sanitize(json_decode(file_get_contents('php://input'), true));
    $requests = Storage::read('requests');

    if (isset($data['id']) && $data['id'] !== 'new') {
        foreach ($requests as &$r) {
            if ($r['id'] === $data['id']) {
                $oldStatus = $r['status'] ?? 'new';

                foreach($data as $key => $value) {
                    if ($key !== 'id') $r[$key] = $value;
                }

                if ($oldStatus !== ($r['status'] ?? '')) {
                    Storage::log("Status changed for request #{$r['id']} to {$r['status']}", $currentUser['id']);
                }
                break;
            }
        }
    } else {
        Auth::requireRole(['admin', 'responsible']);

        $newRequest = [
            'id' => uniqid('req_'),
            'title' => $data['title'] ?? 'Без темы',
            'description' => $data['description'] ?? '',
            'category' => $data['category'] ?? 'IT',
            'priority' => $data['priority'] ?? 'Medium',
            'status' => 'new',
            'creator_id' => $currentUser['id'],
            'executor_id' => $data['executor_id'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'completed_at' => null,
            'sla_deadline' => SLAProvider::calculateDeadline($data['priority'] ?? 'Medium', $data['category'] ?? 'IT'),
            'custom_fields' => $data['custom_fields'] ?? []
        ];

        $requests[] = $newRequest;
        Storage::log("Created request: " . $newRequest['title'], $currentUser['id']);
    }

    Storage::save('requests', $requests);
    echo json_encode(['success' => true]);

} elseif ($method === 'DELETE') {
    Auth::requireAdmin();
    $id = $_GET['id'] ?? '';
    $requests = Storage::read('requests');
    $requests = array_filter($requests, function($r) use ($id) { return $r['id'] !== $id; });
    Storage::save('requests', array_values($requests));
    echo json_encode(['success' => true]);
}
