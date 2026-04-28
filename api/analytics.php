<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

$user = Auth::authenticate();
header('Content-Type: application/json');

$tasks = Storage::read('tasks');
$users = Storage::read('users');

$stats = [
    'byStatus' => [],
    'byPriority' => [],
    'employeeLoad' => [],
    'avgCompletionTime' => 0,
    'total' => count($tasks)
];

$totalCompletedTime = 0;
$completedCount = 0;

foreach ($tasks as $task) {
    // Status stats
    $status = $task['status'];
    $stats['byStatus'][$status] = ($stats['byStatus'][$status] ?? 0) + 1;

    // Priority stats
    $priority = $task['priority'];
    $stats['byPriority'][$priority] = ($stats['byPriority'][$priority] ?? 0) + 1;

    // Load stats
    if ($task['executor_id']) {
        $execName = $task['executor_name'] ?? 'Unknown';
        $stats['employeeLoad'][$execName] = ($stats['employeeLoad'][$execName] ?? 0) + 1;
    }

    // Completion time calculation
    if ($status === 'Completed') {
        $start = strtotime($task['created_at']);
        $end = 0;
        foreach ($task['history'] as $h) {
            if (strpos($h['action'], 'выполнено') !== false || strpos($h['action'], 'Completed') !== false) {
                $end = strtotime($h['time']);
                break;
            }
        }
        if ($end > $start) {
            $totalCompletedTime += ($end - $start);
            $completedCount++;
        }
    }
}

if ($completedCount > 0) {
    $stats['avgCompletionTime'] = round($totalCompletedTime / $completedCount / 3600, 2); // hours
}

echo json_encode($stats);
