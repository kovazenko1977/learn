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
        $data = Security::sanitize($data); // Sanitize input
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

    case 'mark_read':
        $id = $_GET['id'] ?? '';
        $msg = Storage::findOne('messages', ['id' => $id]);
        if ($msg) {
            $user_id = $_SESSION['user_id'];
            if (!in_array($user_id, $msg['read_by'])) {
                $msg['read_by'][] = $user_id;
                Storage::update('messages', $id, ['read_by' => $msg['read_by']]);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Message not found']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
