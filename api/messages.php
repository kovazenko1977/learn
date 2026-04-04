<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

Auth::requireRole(['superadmin', 'admin_communications', 'client']);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $messages = Storage::read('messages');
        $user = Auth::getCurrentUser();

        if ($user['role'] === 'client') {
            $messages = array_filter($messages, function($msg) use ($user) {
                return $msg['to'] === 'all' || $msg['to'] === $user['id'];
            });
        }
        echo json_encode(['success' => true, 'messages' => array_values($messages)]);
        break;

    case 'send':
        Auth::requireRole(['superadmin', 'admin_communications']);
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Security::sanitize($data);
        $id = Storage::insert('messages', [
            'from' => $_SESSION['user_id'],
            'to' => $data['to'] ?? 'all',
            'subject' => $data['subject'] ?? 'Untitled',
            'body' => $data['body'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'read_by' => []
        ]);
        Security::log('send_message', $_SESSION['user_id'], 'messages', ['id' => $id, 'subject' => $data['subject']]);
        echo json_encode(['success' => true, 'id' => $id]);
        break;

    case 'broadcast':
        Auth::requireRole(['superadmin', 'admin_communications']);
        $data = json_decode(file_get_contents('php://input'), true);
        $data = Security::sanitize($data);
        $id = Storage::insert('messages', [
            'from' => $_SESSION['user_id'],
            'to' => 'all',
            'subject' => '[BROADCAST] ' . ($data['subject'] ?? 'Notification'),
            'body' => $data['body'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'read_by' => [],
            'is_broadcast' => true
        ]);
        Security::log('broadcast_message', $_SESSION['user_id'], 'messages', ['id' => $id]);
        echo json_encode(['success' => true]);
        break;

    case 'templates':
        Auth::requireRole(['superadmin', 'admin_communications']);
        $templates = Storage::read('message_templates');
        echo json_encode(['success' => true, 'templates' => $templates]);
        break;

    case 'save_template':
        Auth::requireRole(['superadmin', 'admin_communications']);
        $data = json_decode(file_get_contents('php://input'), true);
        $id = Storage::insert('message_templates', Security::sanitize($data));
        echo json_encode(['success' => true, 'id' => $id]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
