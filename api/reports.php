<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$storage = new Storage(__DIR__ . '/../data');
$user = Auth::check(['admin', 'manager']);
if (!$user) {
    http_response_code(401);
    exit(json_encode(['message' => 'Unauthorized or Insufficient permissions']));
}

$action = $_GET['action'] ?? '';

if ($action == 'summary') {
    $requests = $storage->readCollection('requests');
    $summary = [
        'total' => count($requests),
        'new' => 0,
        'assigned' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'closed' => 0,
        'avg_rating' => 0,
        'overdue' => 0
    ];
    $ratings = [];
    $now = new DateTime();
    foreach ($requests as $r) {
        if (isset($summary[$r['status']])) {
            $summary[$r['status']]++;
        }
        if (isset($r['rating'])) {
            $ratings[] = $r['rating'];
        }
        if (!in_array($r['status'], ['closed', 'completed']) && isset($r['deadline_at'])) {
            $deadline = new DateTime($r['deadline_at']);
            if ($now > $deadline) {
                $summary['overdue']++;
            }
        }
    }
    if (count($ratings)) {
        $summary['avg_rating'] = round(array_sum($ratings) / count($ratings), 1);
    }
    echo json_encode($summary);
} elseif ($action == 'executors') {
    $requests = $storage->readCollection('requests');
    $users = $storage->readCollection('users');
    $executors = array_filter($users, fn($u) => $u['role'] === 'executor');

    $stats = [];
    foreach ($executors as $ex) {
        $count = count(array_filter($requests, fn($r) => $r['assigned_to'] == $ex['id'] && !in_array($r['status'], ['closed', 'completed'])));
        $stats[] = [
            'id' => $ex['id'],
            'full_name' => $ex['full_name'],
            'active_requests' => $count
        ];
    }
    echo json_encode($stats);
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Action not found']);
}
