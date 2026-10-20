<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$storage = Storage::getInstance();
$currentUser = Auth::requireUser();
$action = $_GET['action'] ?? 'dashboard';

if ($action === 'dashboard') {
    $tickets = $storage->get('tickets');
    $users = $storage->get('users');
    $categories = $storage->get('categories');
    $priorities = $storage->get('priorities');

    // Counts by status
    $statusCounts = [
        'new' => 0,
        'assigned' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'rejected' => 0,
        'total' => count($tickets)
    ];

    // SLA statistics
    $slaStats = [
        'ok' => 0,
        'warning' => 0,
        'breached' => 0
    ];

    // Average Resolution Time (MTTR in hours)
    $totalResolutionSec = 0;
    $completedCount = 0;

    // Workload per executor
    $executors = array_filter($users, fn($u) => $u['role'] === 'executor');
    $workload = [];
    foreach ($executors as $e) {
        $workload[$e['id']] = [
            'id' => $e['id'],
            'full_name' => $e['full_name'],
            'department' => $e['department'] ?? '',
            'assigned' => 0,
            'in_progress' => 0,
            'completed' => 0
        ];
    }

    $now = new DateTime();

    foreach ($tickets as $t) {
        $st = $t['status'] ?? 'new';
        if (isset($statusCounts[$st])) {
            $statusCounts[$st]++;
        }

        // Executor workload tracking
        if (!empty($t['assigned_to']) && isset($workload[$t['assigned_to']])) {
            if ($st === 'assigned') $workload[$t['assigned_to']]['assigned']++;
            if ($st === 'in_progress') $workload[$t['assigned_to']]['in_progress']++;
            if ($st === 'completed') $workload[$t['assigned_to']]['completed']++;
        }

        // MTTR calculation
        if ($st === 'completed' && !empty($t['completed_at']) && !empty($t['created_at'])) {
            $cAt = new DateTime($t['created_at']);
            $compAt = new DateTime($t['completed_at']);
            $diffSec = $compAt->getTimestamp() - $cAt->getTimestamp();
            if ($diffSec > 0) {
                $totalResolutionSec += $diffSec;
                $completedCount++;
            }
        }

        // SLA Compliance
        if ($st !== 'completed' && !empty($t['due_date'])) {
            $dueDate = new DateTime($t['due_date']);
            if ($now > $dueDate) {
                $slaStats['breached']++;
            } else {
                $cAt = new DateTime($t['created_at']);
                $totalSec = $dueDate->getTimestamp() - $cAt->getTimestamp();
                $remSec = $dueDate->getTimestamp() - $now->getTimestamp();
                if ($totalSec > 0 && ($remSec / $totalSec) <= 0.20) {
                    $slaStats['warning']++;
                } else {
                    $slaStats['ok']++;
                }
            }
        }
    }

    $avgResolutionHours = $completedCount > 0 ? round(($totalResolutionSec / $completedCount) / 3600, 1) : 0;

    echo json_encode([
        'success' => true,
        'metrics' => [
            'status_counts' => $statusCounts,
            'sla_stats' => $slaStats,
            'avg_resolution_hours' => $avgResolutionHours,
            'completed_count' => $completedCount,
            'executor_workload' => array_values($workload)
        ]
    ]);
    exit;
}

if ($action === 'export-excel') {
    $tickets = $storage->get('tickets');
    $categories = $storage->get('categories');
    $priorities = $storage->get('priorities');
    $users = $storage->get('users');

    $catMap = array_column($categories, 'name', 'id');
    $prioMap = array_column($priorities, 'name', 'id');
    $userMap = array_column($users, 'full_name', 'id');

    $statusLabels = [
        'new' => 'Новая',
        'assigned' => 'Назначена',
        'in_progress' => 'В работе',
        'completed' => 'Выполнена',
        'rejected' => 'Отклонена'
    ];

    // Export as UTF-8 CSV with BOM for Excel compatibility
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=crm_tickets_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    // Output UTF-8 BOM
    fputs($output, "\xEF\xBB\xBF");

    // CSV Header
    fputcsv($output, [
        'Номер заявки',
        'Название',
        'Категория',
        'Приоритет',
        'Статус',
        'Заявитель',
        'Исполнитель',
        'Отдел',
        'Дата создания',
        'Срок SLA',
        'Дата выполнения'
    ], ';');

    foreach ($tickets as $t) {
        fputcsv($output, [
            $t['number'] ?? '',
            $t['title'] ?? '',
            $catMap[$t['category_id']] ?? '',
            $prioMap[$t['priority_id']] ?? '',
            $statusLabels[$t['status']] ?? $t['status'],
            $userMap[$t['created_by']] ?? '',
            $userMap[$t['assigned_to']] ?? 'Не назначен',
            $t['department'] ?? '',
            $t['created_at'] ?? '',
            $t['due_date'] ?? '',
            $t['completed_at'] ?? ''
        ], ';');
    }

    fclose($output);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Неизвестная функция аналитики']);
