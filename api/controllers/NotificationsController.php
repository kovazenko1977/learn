<?php
// api/controllers/NotificationsController.php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../storage.php';

class NotificationsController {
    private $storage;

    public function __construct() {
        $this->storage = Storage::getInstance();
    }

    public function index() {
        $user = require_auth();
        $notifs = $this->storage->all('notifications');

        $userNotifs = array_values(array_filter($notifs, function($n) use ($user) {
            return ($n['user_id'] ?? 'all') === 'all' || (string)($n['user_id'] ?? '') === (string)$user['id'];
        }));

        usort($userNotifs, function($a, $b) {
            return strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0);
        });

        $unreadCount = count(array_filter($userNotifs, fn($n) => empty($n['is_read'])));

        json_out([
            'unread_count' => $unreadCount,
            'notifications' => $userNotifs
        ]);
    }

    public function markAllRead() {
        $user = require_auth();
        $notifs = $this->storage->all('notifications');

        foreach ($notifs as $n) {
            if (($n['user_id'] ?? 'all') === 'all' || (string)($n['user_id'] ?? '') === (string)$user['id']) {
                if (empty($n['is_read'])) {
                    $this->storage->update('notifications', $n['id'], ['is_read' => 1]);
                }
            }
        }

        json_out(['success' => true, 'message' => 'Все уведомления отмечены как прочитанные']);
    }
}
