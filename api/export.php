<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

$user = Auth::getUser();
if (!$user) {
    http_response_code(401);
    die('Unauthorized');
}

$tasks = Storage::get('tasks');
$settings = Storage::get('settings');

// Filter visible tasks
$filtered = array_filter($tasks, function($t) use ($user) {
    if ($user['role'] === 'admin') return true;
    if ($user['role'] === 'head') return $t['department'] === $user['department'];
    if ($user['role'] === 'executor') return $t['executor_id'] === $user['id'];
    if ($user['role'] === 'employee') return $t['creator_id'] === $user['id'];
    return false;
});

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=tasks_export_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header
fputcsv($output, ['ID', 'Заголовок', 'Категория', 'Приоритет', 'Статус', 'Создатель', 'Исполнитель', 'Дата создания', 'Дата завершения']);

foreach ($filtered as $t) {
    fputcsv($output, [
        $t['id'],
        $t['title'],
        $t['category'],
        $t['priority'],
        $t['status'],
        $t['creator_name'],
        $t['executor_name'] ?? '-',
        $t['created_at'],
        $t['completed_at'] ?? '-'
    ]);
}

fclose($output);
