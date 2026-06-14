<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$settingsFile = __DIR__ . '/../data/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];

$storage = new Storage($settings);
$auth = new Auth($storage, $settings);

$auth->requireRole(['admin', 'head']);

$action = $_GET['action'] ?? 'stats';

if ($action === 'export') {
    $tasks = $storage->get('tasks');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=tasks_export.csv');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, ['ID', 'Заголовок', 'Описание', 'Категория', 'Приоритет', 'Статус', 'Создана', 'Дедлайн']);

    foreach ($tasks as $task) {
        fputcsv($output, [
            $task['id'],
            $task['title'],
            $task['description'],
            $task['category'],
            $task['priority'],
            $task['status'],
            $task['created_at'],
            $task['deadline'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

header('Content-Type: application/json');

$tasks = $storage->get('tasks');
$users = $storage->get('users');

$stats = [
    'by_status' => [
        'new' => 0,
        'assigned' => 0,
        'in_work' => 0,
        'completed' => 0,
        'rejected' => 0
    ],
    'by_priority' => [
        'low' => 0,
        'medium' => 0,
        'high' => 0,
        'urgent' => 0
    ],
    'user_load' => [],
    'avg_completion_time' => 0,
    'sla_breaches' => 0
];

$totalCompleted = 0;
$totalCompletionTime = 0;
$now = new DateTime();

foreach ($tasks as $task) {
    $stats['by_status'][$task['status']]++;
    $stats['by_priority'][$task['priority']]++;

    if (isset($task['executor_id']) && $task['executor_id']) {
        $stats['user_load'][$task['executor_id']] = ($stats['user_load'][$task['executor_id']] ?? 0) + 1;
    }

    if ($task['status'] === 'completed' && isset($task['completed_at'])) {
        $created = new DateTime($task['created_at']);
        $completed = new DateTime($task['completed_at']);
        $diff = $completed->getTimestamp() - $created->getTimestamp();
        $totalCompletionTime += $diff;
        $totalCompleted++;
    }

    if ($task['status'] !== 'completed' && isset($task['deadline'])) {
        $deadline = new DateTime($task['deadline']);
        if ($now > $deadline) {
            $stats['sla_breaches']++;
        }
    }
}

if ($totalCompleted > 0) {
    $stats['avg_completion_time'] = round($totalCompletionTime / $totalCompleted / 3600, 1); // hours
}

$userMap = [];
foreach ($users as $u) $userMap[$u['id']] = $u['full_name'] ?: $u['username'];

$loadWithNames = [];
foreach ($stats['user_load'] as $uid => $count) {
    $loadWithNames[] = ['name' => $userMap[$uid] ?? 'Unknown', 'count' => $count];
}
$stats['user_load'] = $loadWithNames;

echo json_encode($stats);
