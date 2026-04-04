<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

Auth::requireRole(['superadmin', 'admin_content', 'admin_clients', 'admin_communications', 'client']);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'summary':
        $users = Storage::read('users');
        $docs = Storage::read('documents');
        $logs = Storage::read('logs');
        $messages = Storage::read('messages');
        $user = Auth::getCurrentUser();

        $clients_count = count(array_filter($users, fn($u) => isset($u['role']) && $u['role'] === 'client'));
        $admins_count = count($users) - $clients_count;
        $active_clients = count(array_filter($users, fn($u) => isset($u['role']) && $u['role'] === 'client' && ($u['status'] ?? '') === 'active'));

        // Storage calculation
        $storage_bytes = 0;
        foreach (glob(__DIR__ . '/../uploads/*.enc') as $file) {
            $storage_bytes += filesize($file);
        }

        $unread_docs = 0;
        $unread_messages = 0;

        foreach ($docs as $d) {
            if ($user['role'] === 'client') {
                if (($d['is_public'] || $d['client_id'] === $user['id']) && !($d['archived'] ?? false)) {
                    if (!in_array($user['id'], $d['read_by'] ?? [])) $unread_docs++;
                }
            }
        }

        foreach ($messages as $m) {
            if ($user['role'] === 'client') {
                if ($m['to'] === 'all' || $m['to'] === $user['id']) {
                    if (!in_array($user['id'], $m['read_by'] ?? [])) $unread_messages++;
                }
            } else {
                if ($m['to'] === 'admin' || $m['to'] === 'all' || $m['to'] === 'superadmin' || $m['to'] === $user['id']) {
                    if (!in_array($user['id'], $m['read_by'] ?? [])) $unread_messages++;
                }
            }
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
                'storage_used' => round($storage_bytes / 1024 / 1024, 2) . ' MB',
                'unread_docs' => $unread_docs,
                'unread_messages' => $unread_messages
            ]
        ]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
