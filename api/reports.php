<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$storage = new Storage(__DIR__ . '/../data');
$user = Auth::check();
if (!$user) {
    http_response_code(401);
    exit(json_encode(['message' => 'Unauthorized']));
}

$perms = $user['permissions'] ?? [];
if ($user['role'] !== 'admin' && $user['role'] !== 'manager' && !($perms['can_view_reports'] ?? false)) {
    http_response_code(403);
    exit(json_encode(['message' => 'Forbidden']));
}

$action = $_GET['action'] ?? '';
$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

function applyAdvancedFilters($items, $params) {
    $from = $params['from'] ?? null;
    $to = $params['to'] ?? null;
    $status = $params['status'] ?? null;
    $priority = $params['priority'] ?? null;
    $wt = $params['work_type_id'] ?? null;
    $req = $params['requester_id'] ?? null;
    $exec = $params['assigned_to'] ?? null;
    $loc = $params['location'] ?? null;
    $q = $params['q'] ?? null;

    return array_filter($items, function($item) use ($from, $to, $status, $priority, $wt, $req, $exec, $loc, $q) {
        if ($from && strtotime($item['created_at']) < strtotime($from)) return false;
        if ($to && strtotime($item['created_at']) > strtotime($to . ' 23:59:59')) return false;
        if ($status && $item['status'] !== $status) return false;
        if ($priority && $item['priority'] !== $priority) return false;
        if ($wt && $item['work_type_id'] != $wt) return false;
        if ($req && $item['requester_id'] != $req) return false;
        if ($exec && ($item['assigned_to'] ?? '') != $exec) return false;
        if ($loc && stripos($item['location'] ?? '', $loc) === false) return false;
        if ($q) {
            $searchStr = ($item['number'] ?? '') . ' ' . ($item['description'] ?? '') . ' ' . ($item['location'] ?? '');
            if (stripos($searchStr, $q) === false) return false;
        }
        return true;
    });
}

if ($action == 'summary') {
    $allRequests = $storage->readCollection('requests');
    $requests = applyAdvancedFilters($allRequests, $_GET);
    $history = $storage->readCollection('status_history');
    $depts = $storage->readCollection('departments');

    // Group history by request ID for efficiency
    $historyByReq = [];
    foreach ($history as $h) {
        $historyByReq[$h['request_id']][] = $h;
    }

    $summary = [
        'total' => count($requests),
        'status_dist' => [
            'new' => 0, 'assigned' => 0, 'in_progress' => 0,
            'completed' => 0, 'closed' => 0, 'rejected' => 0,
            'waiting_parts' => 0, 'postponed' => 0, 'need_info' => 0
        ],
        'priority_dist' => ['high' => 0, 'normal' => 0, 'low' => 0],
        'avg_rating' => 0,
        'overdue' => 0,
        'avg_res_time_hours' => 0,
        'dept_stats' => []
    ];

    $ratings = [];
    $resTimes = [];
    $now = new DateTime();

    $deptMap = [];
    foreach($depts as $d) $deptMap[$d['id']] = ['name' => $d['name'], 'total' => 0, 'completed' => 0, 'ratings' => [], 'overdue' => 0];

    foreach ($requests as $r) {
        if (isset($summary['status_dist'][$r['status']])) $summary['status_dist'][$r['status']]++;
        if (isset($summary['priority_dist'][$r['priority']])) $summary['priority_dist'][$r['priority']]++;

        if (isset($r['rating'])) {
            $ratings[] = $r['rating'];
            if (isset($deptMap[$r['department_id']])) $deptMap[$r['department_id']]['ratings'][] = $r['rating'];
        }

        $isOverdue = false;
        if (!in_array($r['status'], ['closed', 'completed']) && isset($r['deadline_at'])) {
            try {
                $deadline = new DateTime($r['deadline_at']);
                if ($now > $deadline) {
                    $summary['overdue']++;
                    $isOverdue = true;
                }
            } catch (Exception $e) {}
        }

        if (isset($deptMap[$r['department_id']])) {
            $deptMap[$r['department_id']]['total']++;
            if ($r['status'] == 'closed' || $r['status'] == 'completed') $deptMap[$r['department_id']]['completed']++;
            if ($isOverdue) $deptMap[$r['department_id']]['overdue']++;
        }

        // Calc resolution time
        if ($r['status'] == 'completed' || $r['status'] == 'closed') {
            $reqHistory = $historyByReq[$r['id']] ?? [];
            $start = null; $end = null;
            foreach($reqHistory as $h) {
                if ($h['status'] == 'in_progress' && !$start) $start = @strtotime($h['changed_at']);
                if ($h['status'] == 'completed' && !$end) $end = @strtotime($h['changed_at']);
            }
            if ($start && $end && $end > $start) $resTimes[] = ($end - $start) / 3600;
        }
    }

    if (count($ratings)) $summary['avg_rating'] = round(array_sum($ratings) / count($ratings), 1);
    if (count($resTimes)) $summary['avg_res_time_hours'] = round(array_sum($resTimes) / count($resTimes), 1);

    foreach($deptMap as $id => $stats) {
        if ($stats['total'] > 0) {
            $stats['avg_rating'] = count($stats['ratings']) ? round(array_sum($stats['ratings']) / count($stats['ratings']), 1) : 0;
            unset($stats['ratings']);
            $summary['dept_stats'][] = array_merge(['id' => $id], $stats);
        }
    }

    echo json_encode($summary);
} elseif ($action == 'executors') {
    $allRequests = $storage->readCollection('requests');
    $requests = applyAdvancedFilters($allRequests, $_GET);
    $users = $storage->readCollection('users');
    $executors = array_filter($users, fn($u) => $u['role'] === 'executor');

    $stats = [];
    foreach ($executors as $ex) {
        $count = count(array_filter($requests, fn($r) => $r['assigned_to'] == $ex['id'] && !in_array($r['status'], ['closed', 'completed'])));
        $stats[] = [
            'id' => $ex['id'],
            'full_name' => $ex['full_name'],
            'active_requests' => $count
        ];
    }
    echo json_encode($stats);
} elseif ($action == 'executor_activity') {
    $executorId = $_GET['id'] ?? null;
    if (!$executorId) {
        http_response_code(400);
        exit(json_encode(['message' => 'Executor ID required']));
    }

    $requests = $storage->readCollection('requests');
    $history = $storage->readCollection('status_history');
    $comments = $storage->readCollection('comments');

    $executorRequests = array_values(array_filter($requests, fn($r) => $r['assigned_to'] == $executorId));
    $executorHistory = array_values(array_filter($history, fn($h) => $h['changed_by'] == $executorId));
    $executorComments = array_values(array_filter($comments, fn($c) => $c['user_id'] == $executorId));

    $activity = [];
    foreach($executorRequests as $r) {
        $activity[] = [
            'type' => 'assigned',
            'date' => $r['created_at'],
            'request_number' => $r['number'],
            'request_id' => $r['id'],
            'text' => 'Назначена заявка'
        ];
    }
    foreach($executorHistory as $h) {
        $activity[] = [
            'type' => 'status',
            'date' => $h['changed_at'],
            'request_id' => $h['request_id'],
            'text' => 'Смена статуса: ' . $h['status'],
            'comment' => $h['comment']
        ];
    }
    foreach($executorComments as $c) {
        $activity[] = [
            'type' => 'comment',
            'date' => $c['created_at'],
            'request_id' => $c['request_id'],
            'text' => 'Добавлен комментарий',
            'comment' => $c['message']
        ];
    }

    usort($activity, fn($a, $b) => strcmp($b['date'], $a['date']));
    echo json_encode($activity);
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Action not found']);
}
