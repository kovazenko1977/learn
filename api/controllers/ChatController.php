<?php
// api/controllers/ChatController.php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../storage.php';

class ChatController {
    private $storage;

    public function __construct() {
        $this->storage = Storage::getInstance();
    }

    public function get() {
        $user = require_auth();
        $userId = $user['id'];

        if (($user['role'] ?? 'user') === 'admin' && isset($_GET['user_id'])) {
            $userId = $_GET['user_id'];
        }

        $chats = $this->storage->get('chats', ['user_id' => $userId]);
        if (empty($chats)) {
            $chat = [
                'id' => 'chat_' . $userId,
                'user_id' => $userId,
                'user_name' => $user['name'],
                'messages' => [
                    [
                        'id' => 'm_init',
                        'sender' => 'admin',
                        'text' => 'Здравствуйте! Чем я могу вам помочь сегодня? 🌸',
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                ],
                'unread_admin' => 0,
                'unread_user' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $chat = $this->storage->insert('chats', $chat);
        } else {
            $chat = $chats[0];
            // Clear unread flag for viewing user
            if (($user['role'] ?? 'user') === 'admin') {
                if (!empty($chat['unread_admin'])) {
                    $chat = $this->storage->update('chats', $chat['id'], ['unread_admin' => 0]);
                }
            } else {
                if (!empty($chat['unread_user'])) {
                    $chat = $this->storage->update('chats', $chat['id'], ['unread_user' => 0]);
                }
            }
        }

        json_out($chat);
    }

    public function send() {
        $user = require_auth();
        $data = json_in();
        $text = trim($data['text'] ?? '');

        if (empty($text)) {
            json_out(['error' => 'Сообщение не может быть пустым'], 400);
        }

        $targetUserId = $user['id'];
        $senderRole = ($user['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

        if ($senderRole === 'admin' && !empty($data['user_id'])) {
            $targetUserId = $data['user_id'];
        }

        $chats = $this->storage->get('chats', ['user_id' => $targetUserId]);
        if (empty($chats)) {
            $chat = [
                'id' => 'chat_' . $targetUserId,
                'user_id' => $targetUserId,
                'user_name' => $user['name'],
                'messages' => [],
                'unread_admin' => 0,
                'unread_user' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $chat = $this->storage->insert('chats', $chat);
        } else {
            $chat = $chats[0];
        }

        $messages = is_array($chat['messages'] ?? null) ? $chat['messages'] : [];
        $newMsg = [
            'id' => 'msg_' . uniqid(),
            'sender' => $senderRole,
            'text' => $text,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $messages[] = $newMsg;

        $updateData = [
            'messages' => $messages,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($senderRole === 'user') {
            $updateData['unread_admin'] = 1;
        } else {
            $updateData['unread_user'] = 1;
        }

        $updatedChat = $this->storage->update('chats', $chat['id'], $updateData);

        // Auto-reply logic if message sent by normal user
        if ($senderRole === 'user') {
            $settingsList = $this->storage->all('settings');
            $autoReplyText = "Спасибо за ваш запрос! Наш менеджер свяжется с вами в течение 5 минут. 💐";
            if (!empty($settingsList) && !empty($settingsList[0]['auto_reply_text'])) {
                $autoReplyText = $settingsList[0]['auto_reply_text'];
            }

            // Return auto-reply payload indicator so client can display delayed auto-reply
            json_out([
                'chat' => $updatedChat,
                'auto_reply' => [
                    'delay_ms' => 1500,
                    'text' => $autoReplyText
                ]
            ]);
        }

        json_out(['chat' => $updatedChat]);
    }

    public function list() {
        require_admin();
        $chats = $this->storage->all('chats');
        usort($chats, function($a, $b) {
            return strtotime($b['updated_at'] ?? 0) - strtotime($a['updated_at'] ?? 0);
        });
        json_out($chats);
    }
}
