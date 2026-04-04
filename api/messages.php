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
                return $msg['to'] === 'all' || $msg['to'] === $user['id'] || $msg['from'] === $user['id'];
            });
        } else {
            // Admins see all messages addressed to 'admin', all broadcasts, and all messages they sent
            $messages = array_filter($messages, function($msg) use ($user) {
                $isRelevant = ($msg['to'] ?? '') === 'admin' || ($msg['to'] ?? '') === 'all' || $msg['from'] === $user['id'] || ($msg['to'] ?? '') === 'superadmin';

                $sender = Storage::findOne('users', ['id' => $msg['from']]);
                $isFromClient = $sender && ($sender['role'] ?? '') === 'client';

                $recipient = Storage::findOne('users', ['id' => ($msg['to'] ?? '')]);
                $isToClient = $recipient && ($recipient['role'] ?? '') === 'client';

                return $isRelevant || $isFromClient || $isToClient;
            });
        }
        echo json_encode(['success' => true, 'messages' => array_values($messages)]);
        break;

    case 'send':
        $data = $_POST;
        if (empty($data)) {
            $data = json_decode(file_get_contents('php://input'), true);
        }
        $data = Security::sanitize($data);

        $attachments = [];
        if (!empty($_FILES['attachment'])) {
            $tmp_name = $_FILES['attachment']['tmp_name'];
            $orig_name = Security::sanitize($_FILES['attachment']['name']);
            $att_id = uniqid('att_');
            $dest = __DIR__ . '/../uploads/' . $att_id . '.enc';
            if (Security::encryptFile($tmp_name, $dest)) {
                $attachments[] = [
                    'id' => $att_id,
                    'name' => $orig_name,
                    'uploaded_at' => date('Y-m-d H:i:s')
                ];
            }
        }

        $user = Auth::getCurrentUser();
        $is_client = $user['role'] === 'client';

        $message = [
            'from' => $_SESSION['user_id'],
            'from_name' => $user['username'],
            'to' => $is_client ? 'admin' : ($data['to'] ?? 'all'),
            'subject' => $data['subject'] ?? 'Untitled',
            'body' => $data['body'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'read_by' => [],
            'attachments' => $attachments,
            'parent_id' => $data['parent_id'] ?? null
        ];

        $id = Storage::insert('messages', $message);
        Security::log('send_message', $_SESSION['user_id'], 'messages', ['id' => $id]);
        echo json_encode(['success' => true, 'id' => $id]);
        break;

    case 'mark_read':
        $id = $_GET['id'] ?? '';
        $msg = Storage::findOne('messages', ['id' => $id]);
        if ($msg) {
            $user = Auth::getCurrentUser();
            if ($user['role'] === 'client') {
                if ($msg['to'] !== 'all' && $msg['to'] !== $user['id'] && $msg['from'] !== $user['id']) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Forbidden']);
                    exit;
                }
            }
            $read_by = $msg['read_by'] ?? [];
            if (!in_array($_SESSION['user_id'], $read_by)) {
                $read_by[] = $_SESSION['user_id'];
                Storage::update('messages', $id, ['read_by' => $read_by]);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Message not found']);
        }
        break;

    case 'download_attachment':
        $att_id = $_GET['id'] ?? '';
        $messages = Storage::read('messages');
        $user = Auth::getCurrentUser();
        $found = false;
        $file_name = '';
        foreach ($messages as $msg) {
            foreach ($msg['attachments'] as $att) {
                if ($att['id'] === $att_id) {
                    if ($user['role'] === 'client') {
                        if ($msg['to'] !== 'all' && $msg['to'] !== $user['id'] && $msg['from'] !== $user['id']) {
                            http_response_code(403);
                            echo json_encode(['error' => 'Forbidden']);
                            exit;
                        }
                    }
                    $found = true;
                    $file_name = $att['name'];
                    break 2;
                }
            }
        }

        if (!$found) {
            http_response_code(404);
            echo json_encode(['error' => 'Attachment not found']);
            break;
        }

        $path = __DIR__ . '/../uploads/' . $att_id . '.enc';
        if (!file_exists($path)) {
            http_response_code(404);
            echo json_encode(['error' => 'File missing']);
            break;
        }

        Security::log('download_attachment', $_SESSION['user_id'], 'messages', ['id' => $att_id]);
        Security::decryptFile($path, $file_name);
        exit;

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
            'is_broadcast' => true,
            'attachments' => []
        ]);
        Security::log('broadcast_message', $_SESSION['user_id'], 'messages', ['id' => $id]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
