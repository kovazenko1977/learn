<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/TokenProvider.php';

$storage = new Storage();
$currentUser = TokenProvider::getCurrentUser();

if (!$currentUser || !in_array($currentUser['role'], ['Administrator', 'Department Head'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$action = $_GET['action'] ?? 'stats';

if ($action === 'stats') {
    header('Content-Type: application/json');
    $tasks = $storage->getAll('tasks');
    $users = $storage->getAll('users');

    $stats = [
        'by_status' => [
            'new' => 0,
            'assigned' => 0,
            'in_work' => 0,
            'done' => 0,
            'rejected' => 0
        ],
        'total' => count($tasks),
        'by_executor' => [],
        'avg_done_time' => 0
    ];

    $doneTimes = [];
    foreach ($tasks as $task) {
        $status = $task['status'] ?? 'new';
        if (!isset($stats['by_status'][$status])) {
            $stats['by_status'][$status] = 0;
        }
        $stats['by_status'][$status]++;
        if (!empty($task['executor_id'])) {
            $stats['by_executor'][$task['executor_id']] = ($stats['by_executor'][$task['executor_id']] ?? 0) + 1;
        }
        if ($status === 'done' && !empty($task['completed_at'])) {
            $doneTimes[] = strtotime($task['completed_at']) - strtotime($task['created_at']);
        }
    }

    if (count($doneTimes) > 0) {
        $stats['avg_done_time'] = array_sum($doneTimes) / count($doneTimes);
    }

    echo json_encode($stats);
    exit;
}

if ($action === 'export') {
    $tasks = $storage->getAll('tasks');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=tasks_export.csv');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, ['ID', 'Title', 'Status', 'Priority', 'Category', 'Creator', 'Executor', 'Deadline', 'Created At'], ';');

    foreach ($tasks as $t) {
        fputcsv($output, [
            $t['id'] ?? '-',
            $t['title'] ?? '-',
            $t['status'] ?? '-',
            $t['priority'] ?? '-',
            $t['category'] ?? '-',
            $t['creator_id'] ?? '-',
            $t['executor_id'] ?? '-',
            $t['deadline'] ?? '-',
            $t['created_at'] ?? '-'
        ], ';');
    }
    fclose($output);
    exit;
}
