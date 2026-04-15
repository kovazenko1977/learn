<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

Auth::requireAuth();

$clients = Storage::read('clients');
$leads = Storage::read('leads');
$tasks = Storage::read('tasks');

$stats = [
    'total_clients' => count($clients),
    'total_leads' => count($leads),
    'pending_tasks' => count(array_filter($tasks, function($t) { return ($t['status'] ?? '') !== 'completed'; })),
    'leads_by_status' => [],
    'recent_logs' => array_reverse(array_slice(Storage::read('logs'), -10))
];

foreach ($leads as $lead) {
    $status = $lead['status'] ?? 'new';
    if (!isset($stats['leads_by_status'][$status])) {
        $stats['leads_by_status'][$status] = 0;
    }
    $stats['leads_by_status'][$status]++;
}

echo json_encode($stats);
