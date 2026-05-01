<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

header('Content-Type: application/json');

$user = Auth::checkRole(['admin', 'head']);
$storage = new Storage();

$requests = $storage->getAll('requests');
$users = $storage->getAll('users');

$stats = [
    'by_status' => [
        'new' => 0,
        'assigned' => 0,
        'in_work' => 0,
        'completed' => 0,
        'rejected' => 0
    ],
    'by_priority' => [],
    'executors_load' => [],
    'sla_stats' => [
        'on_time' => 0,
        'overdue' => 0
    ]
];

$now = time();

foreach ($requests as $req) {
    // Status stats
    if (isset($stats['by_status'][$req['status']])) {
        $stats['by_status'][$req['status']]++;
    }

    // Priority stats
    $p = $req['priority'] ?? 'Не указан';
    if (!isset($stats['by_priority'][$p])) $stats['by_priority'][$p] = 0;
    $stats['by_priority'][$p]++;

    // Executor load
    if ($req['executor_id']) {
        if (!isset($stats['executors_load'][$req['executor_id']])) {
            $stats['executors_load'][$req['executor_id']] = 0;
        }
        if ($req['status'] !== 'completed' && $req['status'] !== 'rejected') {
            $stats['executors_load'][$req['executor_id']]++;
        }
    }

    // SLA stats
    if ($req['status'] !== 'completed' && $req['status'] !== 'rejected') {
        if (strtotime($req['deadline']) < $now) {
            $stats['sla_stats']['overdue']++;
        } else {
            $stats['sla_stats']['on_time']++;
        }
    }
}

// Map executor names
$executorNames = [];
foreach ($users as $u) {
    if ($u['role'] === 'executor') {
        $executorNames[$u['id']] = $u['name'];
    }
}

$readableLoad = [];
foreach ($stats['executors_load'] as $eid => $count) {
    $name = $executorNames[$eid] ?? $eid;
    $readableLoad[$name] = $count;
}
$stats['executors_load'] = $readableLoad;

echo json_encode($stats);
