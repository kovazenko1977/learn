<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/TokenProvider.php';

$settings = json_decode(file_get_contents(__DIR__ . '/../data/settings.json'), true);
$storage = new Storage($settings);
$tokenProvider = new TokenProvider();

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);
if (!$token && isset($_GET['token'])) $token = $_GET['token'];
$userData = $tokenProvider->validateToken($token);

if (!$userData || $userData['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$action = $_GET['action'] ?? 'dashboard';

if ($action === 'dashboard') {
    $tasks = $storage->getTasks();
    $users = $storage->getUsers();

    $stats = [
        'status_counts' => [
            'new' => 0, 'assigned' => 0, 'in_work' => 0, 'done' => 0, 'rejected' => 0
        ],
        'avg_execution_time' => 0,
        'user_load' => [],
        'sla_compliance' => [
            'compliant' => 0,
            'overdue' => 0,
            'rate' => 100
        ],
        'work_category_counts' => []
    ];

    $totalExecutionTime = 0;
    $completedCount = 0;

    $executors = array_filter($users, function($u) { return $u['role'] === 'Executor'; });
    foreach ($executors as $e) {
        $stats['user_load'][$e['id']] = [
            'name' => $e['full_name'],
            'count' => 0,
            'hours' => 0
        ];
    }

    foreach ($tasks as $t) {
        if (isset($stats['status_counts'][$t['status']])) {
            $stats['status_counts'][$t['status']]++;
        }

        // Category breakdown
        $cat = $t['category'] ?: 'Other';
        if (!isset($stats['work_category_counts'][$cat])) {
            $stats['work_category_counts'][$cat] = 0;
        }
        $stats['work_category_counts'][$cat]++;

        // Average execution time
        if (($t['status'] === 'done' || $t['status'] === 'rejected') && !empty($t['completed_at'])) {
            $start = strtotime($t['created_at']);
            $end = strtotime($t['completed_at']);
            $totalExecutionTime += ($end - $start);
            $completedCount++;

            // SLA compliance for completed tasks
            if (!empty($t['deadline'])) {
                if (strtotime($t['completed_at']) <= strtotime($t['deadline'])) {
                    $stats['sla_compliance']['compliant']++;
                } else {
                    $stats['sla_compliance']['overdue']++;
                }
            }
        } else {
            // Active task SLA check
            if (!empty($t['deadline'])) {
                if (time() > strtotime($t['deadline'])) {
                    $stats['sla_compliance']['overdue']++;
                } else {
                    $stats['sla_compliance']['compliant']++;
                }
            }
        }

        if (!empty($t['assigned_to']) && isset($stats['user_load'][$t['assigned_to']])) {
            $stats['user_load'][$t['assigned_to']]['count']++;
        }

        // Sum work logs hours
        if (isset($t['work_logs']) && is_array($t['work_logs'])) {
            foreach ($t['work_logs'] as $wl) {
                $uid = $wl['user_id'];
                if (isset($stats['user_load'][$uid])) {
                    $stats['user_load'][$uid]['hours'] += floatval($wl['hours']);
                }
            }
        }
    }

    if ($completedCount > 0) {
        $stats['avg_execution_time'] = round($totalExecutionTime / $completedCount / 3600, 2); // hours
    }

    $totalSlaTasks = $stats['sla_compliance']['compliant'] + $stats['sla_compliance']['overdue'];
    if ($totalSlaTasks > 0) {
        $stats['sla_compliance']['rate'] = round(($stats['sla_compliance']['compliant'] / $totalSlaTasks) * 100, 1);
    }

    echo json_encode($stats);
} elseif ($action === 'export_csv') {
    $tasks = $storage->getTasks();
    $filename = "report_" . date('Y-m-d') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, ['ID', 'Тема', 'Категория', 'Приоритет', 'Статус', 'Отдел создателя', 'Создано', 'Выполнено', 'Срок SLA', 'Потрачено часов'], ';');

    foreach ($tasks as $t) {
        $totalHours = 0;
        if (isset($t['work_logs']) && is_array($t['work_logs'])) {
            foreach ($t['work_logs'] as $wl) {
                $totalHours += floatval($wl['hours']);
            }
        }

        fputcsv($output, [
            $t['id'] ?? '-',
            $t['title'] ?? '-',
            $t['category'] ?? '-',
            $t['priority'] ?? '-',
            $t['status'] ?? '-',
            $t['creator_department'] ?? '-',
            $t['created_at'] ?? '-',
            $t['completed_at'] ?? '-',
            $t['deadline'] ?? '-',
            $totalHours
        ], ';');
    }
    fclose($output);
    exit;
}
