<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

Auth::requireRole(['superadmin', 'admin_content', 'admin_clients', 'admin_communications']);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $logs = Storage::read('logs');
        $user = Auth::getCurrentUser();

        if ($user['role'] !== 'superadmin') {
            $logs = array_filter($logs, function($log) {
                return $log['user_id'] === $_SESSION['user_id'];
            });
        }
        echo json_encode(['success' => true, 'logs' => array_values($logs)]);
        break;

    case 'export':
        $logs = Storage::read('logs');
        $user = Auth::getCurrentUser();
        if ($user['role'] !== 'superadmin') {
             $logs = array_filter($logs, function($log) {
                return $log['user_id'] === $_SESSION['user_id'];
            });
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="logs_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Type', 'User ID', 'Object', 'Date', 'IP']);
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['type'],
                $log['user_id'],
                $log['object'],
                $log['date'],
                $log['ip']
            ]);
        }
        fclose($output);
        exit;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
