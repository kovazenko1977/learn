<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

$user = Auth::authenticate();
$tasks = Storage::read('tasks');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=tasks_export_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
// Add BOM for Excel Russian support
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['ID', 'Title', 'Priority', 'Status', 'Creator', 'Executor', 'Created At']);

foreach ($tasks as $task) {
    fputcsv($output, [
        $task['id'],
        $task['title'],
        $task['priority'],
        $task['status'],
        $task['creator_name'],
        $task['executor_name'] ?? 'Not assigned',
        $task['created_at']
    ]);
}

fclose($output);
