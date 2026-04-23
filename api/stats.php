<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

Auth::requireAuth();

$type = $_GET['type'] ?? 'stats';

if ($type === 'stats') {
    $requests = Storage::read('requests');
    $users = Storage::read('users');

    $stats = [
        'status_new' => 0,
        'status_in_work' => 0,
        'status_completed' => 0,
        'sla_overdue' => 0,
        'employee_load' => [],
        'recent_logs' => array_reverse(array_slice(Storage::read('logs'), -10))
    ];

    $loadMap = [];
    foreach ($requests as $r) {
        $status = $r['status'] ?? 'new';
        if ($status === 'new') $stats['status_new']++;
        if ($status === 'in_work') $stats['status_in_work']++;
        if ($status === 'completed') $stats['status_completed']++;

        if ($status !== 'completed' && !empty($r['sla_deadline']) && strtotime($r['sla_deadline']) < time()) {
            $stats['sla_overdue']++;
        }

        if (!empty($r['executor_id'])) {
            $eid = $r['executor_id'];
            if (!isset($loadMap[$eid])) $loadMap[$eid] = 0;
            $loadMap[$eid]++;
        }
    }

    foreach ($users as $u) {
        if (($u['role'] ?? '') === 'executor' || isset($loadMap[$u['id']])) {
            $count = $loadMap[$u['id']] ?? 0;
            $stats['employee_load'][] = [
                'name' => $u['name'] ?? $u['username'] ?? 'Unknown',
                'count' => $count,
                'percentage' => count($requests) > 0 ? round(($count / count($requests)) * 100) : 0
            ];
        }
    }

    echo json_encode($stats);

} elseif ($type === 'export') {
    $requests = Storage::read('requests');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=requests_export.csv');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['ID', 'Title', 'Category', 'Priority', 'Status', 'Creator', 'Executor', 'Created At', 'SLA Deadline']);

    foreach ($requests as $r) {
        fputcsv($output, [
            $r['id'],
            $r['title'],
            $r['category'],
            $r['priority'],
            $r['status'],
            $r['creator_id'],
            $r['executor_id'],
            $r['created_at'],
            $r['sla_deadline']
        ]);
    }
    fclose($output);
}
