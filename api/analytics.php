<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$settingsFile = __DIR__ . '/../data/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];

$storage = new Storage($settings);
$auth = new Auth($storage, $settings);

$auth->requireRole(['admin', 'head']);

$action = $_GET['action'] ?? '';

if ($action === 'export') {
    $tasks = $storage->get('tasks');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=tasks_export.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Title', 'Status', 'Priority', 'Category', 'Created At', 'Deadline', 'Completed At']);
    foreach ($tasks as $task) {
        fputcsv($output, [
            $task['id'],
            $task['title'],
            $task['status'],
            $task['priority'],
            $task['category'],
            $task['created_at'],
            $task['deadline'] ?? '',
            $task['completed_at'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

$tasks = $storage->get('tasks');
$users = $storage->get('users');
$userMap = [];
foreach ($users as $u) $userMap[$u['id']] = $u['full_name'] ?: $u['username'];

$stats = [
    'by_status' => [
        'new' => 0,
        'assigned' => 0,
        'in_work' => 0,
        'completed' => 0,
        'rejected' => 0
    ],
    'sla_breaches' => 0,
    'avg_completion_time' => 0,
    'user_load' => []
];

$completionTimes = [];
$now = new DateTime();

foreach ($tasks as $task) {
    if (isset($stats['by_status'][$task['status']])) {
        $stats['by_status'][$task['status']]++;
    }

    if ($task['status'] !== 'completed' && isset($task['deadline'])) {
        $deadline = new DateTime($task['deadline']);
        if ($now > $deadline) $stats['sla_breaches']++;
    }

    if ($task['status'] === 'completed' && isset($task['completed_at'])) {
        $start = new DateTime($task['created_at']);
        $end = new DateTime($task['completed_at']);
        $completionTimes[] = ($end->getTimestamp() - $start->getTimestamp()) / 3600;
    }

    if (isset($task['executor_id'])) {
        $eid = $task['executor_id'];
        if (!isset($stats['user_load'][$eid])) {
            $stats['user_load'][$eid] = ['name' => $userMap[$eid] ?? 'Unknown', 'count' => 0];
        }
        $stats['user_load'][$eid]['count']++;
    }
}

if (count($completionTimes) > 0) {
    $stats['avg_completion_time'] = round(array_sum($completionTimes) / count($completionTimes), 1);
}

$stats['user_load'] = array_values($stats['user_load']);

echo json_encode($stats);
