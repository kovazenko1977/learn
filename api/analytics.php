<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/SLAProvider.php';

$user = Auth::getUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($user['role'] !== 'admin' && $user['role'] !== 'head') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$tasks = Storage::get('tasks');
$users = Storage::get('users');

$stats = [
    'total_tasks' => count($tasks),
    'status_distribution' => [],
    'priority_distribution' => [],
    'load_by_executor' => [],
    'avg_execution_time' => 0, // in hours
    'sla_compliance' => 0 // percentage
];

$totalExecutionTime = 0;
$completedTasks = 0;
$onTimeTasks = 0;

foreach ($tasks as $t) {
    // Status distribution
    $status = $t['status'];
    $stats['status_distribution'][$status] = ($stats['status_distribution'][$status] ?? 0) + 1;

    // Priority distribution
    $priority = $t['priority'];
    $stats['priority_distribution'][$priority] = ($stats['priority_distribution'][$priority] ?? 0) + 1;

    // Load by executor
    if (!empty($t['executor_id'])) {
        $execName = $t['executor_name'] ?? 'Unknown';
        $stats['load_by_executor'][$execName] = ($stats['load_by_executor'][$execName] ?? 0) + 1;
    }

    // SLA & Execution time
    if ($t['status'] === 'completed' && !empty($t['completed_at'])) {
        $completedTasks++;
        $duration = (strtotime($t['completed_at']) - strtotime($t['created_at'])) / 3600;
        $totalExecutionTime += $duration;

        if (!SLAProvider::isOverdue($t)) {
            $onTimeTasks++;
        }
    } elseif ($t['status'] !== 'completed') {
        if (!SLAProvider::isOverdue($t)) {
            $onTimeTasks++;
        }
    }
}

if ($completedTasks > 0) {
    $stats['avg_execution_time'] = round($totalExecutionTime / $completedTasks, 1);
}

if (count($tasks) > 0) {
    $stats['sla_compliance'] = round(($onTimeTasks / count($tasks)) * 100, 1);
}

echo json_encode($stats);
