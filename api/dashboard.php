<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';

$user = Auth::authenticate();
Auth::checkRole($user, ['Administrator', 'Head of Department']);

$storage = Storage::getInstance();
$tasks = $storage->read('tasks');
$users = $storage->read('users');

$stats = [
    'total' => count($tasks),
    'by_status' => [
        'New' => 0,
        'In Work' => 0,
        'Completed' => 0,
        'Rejected' => 0
    ],
    'sla' => [
        'on_time' => 0,
        'overdue' => 0
    ],
    'workload' => [],
    'avg_completion_time' => 0
];

$now = time();
$completedTimes = [];

foreach ($tasks as $t) {
    $stats['by_status'][$t['status']] = ($stats['by_status'][$t['status']] ?? 0) + 1;

    if ($t['status'] === 'Completed') {
        $finishedAt = null;
        foreach (array_reverse($t['history']) as $h) {
            if (str_contains($h['msg'], 'to Completed')) {
                $finishedAt = strtotime($h['at']);
                break;
            }
        }
        if ($finishedAt) {
            $completedTimes[] = $finishedAt - strtotime($t['created_at']);
        }
    }

    $deadline = strtotime($t['deadline']);
    if ($t['status'] !== 'Completed') {
        if ($now > $deadline) {
            $stats['sla']['overdue']++;
        } else {
            $stats['sla']['on_time']++;
        }
    }

    if ($t['assigned_to']) {
        $stats['workload'][$t['assigned_to']] = ($stats['workload'][$t['assigned_to']] ?? 0) + 1;
    }
}

// Map workload to names
$workloadWithNames = [];
foreach ($users as $u) {
    if ($u['role'] === 'Executor' || $u['role'] === 'Administrator') {
        $workloadWithNames[] = [
            'name' => $u['full_name'],
            'count' => $stats['workload'][$u['id']] ?? 0
        ];
    }
}
$stats['workload'] = $workloadWithNames;

if (count($completedTimes) > 0) {
    $stats['avg_completion_time'] = round(array_sum($completedTimes) / count($completedTimes) / 3600, 1); // in hours
}

echo json_encode($stats);
