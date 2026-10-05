<?php
/**
 * ChatController
 */

class ChatController {
    public function handle($method) {
        $currentUser = require_auth();

        if ($method === 'GET') {
            $targetUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $currentUser['id'];

            if (($currentUser['role'] ?? '') !== 'admin' && $targetUserId !== (int)$currentUser['id']) {
                json_out(['error' => 'Доступ запрещён'], 403);
            }

            $this->getHistory($targetUserId);
        } elseif ($method === 'POST') {
            $this->sendMessage($currentUser);
        } else {
            json_out(['error' => 'Method not allowed'], 405);
        }
    }

    private function getHistory($userId) {
        $db = get_storage();
        $chats = $db->get('chats');
        $userChats = array_values(array_filter($chats, function($c) use ($userId) {
            return isset($c['user_id']) && $c['user_id'] == $userId;
        }));

        json_out(['messages' => $userChats]);
    }

    private function sendMessage($currentUser) {
        $data = json_in();
        $text = clean($data['text'] ?? $data['message'] ?? '');
        $targetUserId = isset($data['user_id']) ? (int)$data['user_id'] : $currentUser['id'];

        if (!$text) {
            json_out(['error' => 'Сообщение не может быть пустым'], 400);
        }

        $isAdmin = ($currentUser['role'] ?? '') === 'admin';
        if (!$isAdmin) {
            $targetUserId = $currentUser['id'];
        }

        $db = get_storage();
        $msg = [
            'user_id' => $targetUserId,
            'sender' => $isAdmin ? 'admin' : 'user',
            'text' => $text,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $id = $db->insert('chats', $msg);
        $msg['id'] = $id;

        // Auto-reply if sent by user
        if (!$isAdmin) {
            $settingsList = $db->get('settings');
            $settings = array_values($settingsList)[0] ?? [];
            $autoReplyText = $settings['auto_reply'] ?? 'Спасибо за обращение! Менеджер ответит вам в ближайшее время.';

            // Schedule or immediate insert auto-reply
            $autoMsg = [
                'user_id' => $targetUserId,
                'sender' => 'admin',
                'text' => $autoReplyText,
                'created_at' => date('Y-m-d H:i:s', time() + 1)
            ];
            $db->insert('chats', $autoMsg);
        }

        json_out(['success' => true, 'message' => $msg]);
    }
}
