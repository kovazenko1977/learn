<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

Auth::requireRole(['superadmin', 'admin_content', 'admin_clients', 'admin_communications']);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'summary':
        $users = Storage::read('users');
        $docs = Storage::read('documents');
        $logs = Storage::read('logs');
        $messages = Storage::read('messages');

        $clients_count = count(array_filter($users, fn($u) => $u['role'] === 'client'));
        $admins_count = count($users) - $clients_count;
        $active_clients = count(array_filter($users, fn($u) => $u['role'] === 'client' && $u['status'] === 'active'));

        // Storage calculation
        $storage_bytes = 0;
        foreach (glob(__DIR__ . '/../uploads/*.enc') as $file) {
            $storage_bytes += filesize($file);
        }

        echo json_encode([
            'success' => true,
            'stats' => [
                'total_clients' => $clients_count,
                'active_clients' => $active_clients,
                'total_admins' => $admins_count,
                'total_documents' => count($docs),
                'total_messages' => count($messages),
                'total_logs' => count($logs),
                'storage_used' => round($storage_bytes / 1024 / 1024, 2) . ' MB'
            ]
        ]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
