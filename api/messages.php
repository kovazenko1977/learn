<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

Auth::requireRole(['superadmin', 'admin_communications', 'client']);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $messages = Storage::read('messages');
        $user = Auth::getCurrentUser();

        $allUsers = Storage::read('users');
        $usersById = [];
        foreach ($allUsers as $u) {
            $usersById[$u['id']] = $u;
        }

        if ($user['role'] === 'client') {
            $messages = array_filter($messages, function($msg) use ($user) {
                return $msg['to'] === 'all' || $msg['to'] === $user['id'] || $msg['from'] === $user['id'];
            });
        } else {
            // Admins see all messages addressed to 'admin', all broadcasts, and all messages they sent
            $messages = array_filter($messages, function($msg) use ($user, $usersById) {
                $isRelevant = ($msg['to'] ?? '') === 'admin' || ($msg['to'] ?? '') === 'all' || $msg['from'] === $user['id'] || ($msg['to'] ?? '') === 'superadmin';

                $sender = $usersById[$msg['from']] ?? null;
                $isFromClient = $sender && ($sender['role'] ?? '') === 'client';

                $recipient = $usersById[$msg['to'] ?? ''] ?? null;
                $isToClient = $recipient && ($recipient['role'] ?? '') === 'client';

                return $isRelevant || $isFromClient || $isToClient;
            });
        }
        // Enhance messages with "read_by_partner" flag for UI
        $enhanced = [];
        foreach ($messages as $msg) {
            $partner_id = $msg['from'] === $user['id'] ? $msg['to'] : $msg['from'];
            $msg['read_by_partner'] = false;
            if ($user['role'] === 'client') {
                // If I am client, partner is admin. Check if any admin read it.
                foreach ($msg['read_by'] ?? [] as $reader_id) {
                    $reader = $usersById[$reader_id] ?? null;
                    if ($reader && strpos($reader['role'] ?? '', 'admin') !== false) {
                        $msg['read_by_partner'] = true;
                        break;
                    }
                }
            } else {
                // If I am admin, partner is likely a client. Check if they read it.
                if (in_array($partner_id, $msg['read_by'] ?? [])) {
                    $msg['read_by_partner'] = true;
                }
            }
            $enhanced[] = $msg;
        }

        echo json_encode(['success' => true, 'messages' => array_values($enhanced)]);
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

        // Email Notification
        require_once __DIR__ . '/../includes/Mailer.php';
        if ($message['to'] === 'all') {
            $users = Storage::read('users');
            foreach ($users as $u) {
                if (($u['role'] ?? '') === 'client' && !empty($u['email'])) {
                    Mailer::notifyNewMessage($u['id'], $user['username']);
                }
            }
        } else if ($message['to'] !== 'admin' && $message['to'] !== 'superadmin') {
            Mailer::notifyNewMessage($message['to'], $user['username']);
        } else {
            // Message to admin - notify communications admin
            $admins = Storage::read('users');
            foreach ($admins as $adm) {
                if ($adm['role'] === 'admin_communications' && !empty($adm['email'])) {
                    Mailer::notifyNewMessage($adm['id'], $user['username']);
                }
            }
        }

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

    case 'mark_all_read':
        $user = Auth::getCurrentUser();
        $client_id = $_GET['client_id'] ?? '';
        $messages = Storage::read('messages');
        $updated = false;

        foreach ($messages as &$msg) {
            $isRelevant = false;
            if ($user['role'] === 'client') {
                // Client marking their own messages (from admin or all) as read
                $isRelevant = $msg['to'] === $user['id'] || $msg['to'] === 'all';
            } else {
                // Admin marking messages from a specific client as read
                $isRelevant = $msg['from'] === $client_id && $msg['to'] === 'admin';
            }

            if ($isRelevant && !in_array($user['id'], $msg['read_by'] ?? [])) {
                $msg['read_by'][] = $user['id'];
                $updated = true;
            }
        }

        if ($updated) {
            Storage::write('messages', $messages);
        }
        echo json_encode(['success' => true]);
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

    case 'delete_chat':
        Auth::requireRole(['superadmin', 'admin_communications']);
        $client_id = $_GET['client_id'] ?? '';
        if (!$client_id) {
            echo json_encode(['success' => false, 'error' => 'Missing client_id']);
            break;
        }

        $messages = Storage::read('messages');
        $to_keep = array_filter($messages, function($msg) use ($client_id) {
            return $msg['from'] !== $client_id && $msg['to'] !== $client_id;
        });

        Storage::write('messages', array_values($to_keep));
        Security::log('delete_chat', $_SESSION['user_id'], 'messages', ['client_id' => $client_id]);
        echo json_encode(['success' => true]);
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
