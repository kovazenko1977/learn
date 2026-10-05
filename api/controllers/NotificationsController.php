<?php
/**
 * NotificationsController
 */

class NotificationsController {
    public function handle($method) {
        $currentUser = require_auth();

        if ($method === 'GET') {
            $this->getNotifications($currentUser['id']);
        } elseif ($method === 'PUT') {
            $this->markRead($currentUser['id']);
        } elseif ($method === 'POST') {
            $action = $_GET['action'] ?? '';
            if ($action === 'read-all' || (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], 'read-all'))) {
                $this->markAllRead($currentUser['id']);
            } else {
                $this->markRead($currentUser['id']);
            }
        } else {
            json_out(['error' => 'Method not allowed'], 405);
        }
    }

    public function actionReadAll() {
        $currentUser = require_auth();
        $this->markAllRead($currentUser['id']);
    }

    private function getNotifications($userId) {
        $db = get_storage();
        $notifs = $db->get('notifications');

        $userNotifs = array_values(array_filter($notifs, function($n) use ($userId) {
            return isset($n['user_id']) && $n['user_id'] == $userId;
        }));

        // Sort descending by created_at
        usort($userNotifs, function($a, $b) {
            return strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0);
        });

        json_out(['notifications' => $userNotifs]);
    }

    private function markRead($userId) {
        $data = json_in();
        $id = $data['id'] ?? null;

        if (!$id) {
            json_out(['error' => 'ID уведомления не указан'], 400);
        }

        $db = get_storage();
        $db->update('notifications', $id, ['is_read' => true]);
        json_out(['success' => true]);
    }

    private function markAllRead($userId) {
        $db = get_storage();
        $notifs = $db->get('notifications');

        foreach ($notifs as $n) {
            if (isset($n['user_id']) && $n['user_id'] == $userId && empty($n['is_read'])) {
                $db->update('notifications', $n['id'], ['is_read' => true]);
            }
        }

        json_out(['success' => true]);
    }
}
