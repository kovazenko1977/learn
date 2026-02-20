<?php
namespace Hop\Core;

class NotificationManager {
    private JsonStore $settingsStore;
    private JsonStore $notificationStore;
    private ?JsonStore $userStore;

    public function __construct(JsonStore $settingsStore, JsonStore $notificationStore, ?JsonStore $userStore = null) {
        $this->settingsStore = $settingsStore;
        $this->notificationStore = $notificationStore;
        $this->userStore = $userStore;
    }

    public function send(int $userId, string $message, string $type = 'info'): bool {
        // Internal log
        $notifications = $this->notificationStore->read();

        $notifications[] = [
            'user_id' => $userId,
            'message' => $message,
            'type' => $type,
            'timestamp' => date('Y-m-d H:i:s'),
            'read' => false
        ];

        $this->notificationStore->save($notifications);

        // Telegram Notification
        $settings = $this->settingsStore->read();
        if (!empty($settings['telegram_token'])) {
            $token = $settings['telegram_token'];
            $targetChatId = null;

            // Check if user has personal chat ID
            if ($this->userStore) {
                $users = $this->userStore->read();
                foreach ($users as $u) {
                    if ($u['id'] === $userId && !empty($u['telegram_chat_id'])) {
                        $targetChatId = $u['telegram_chat_id'];
                        break;
                    }
                }
            }

            // Fallback to global chat ID if personal not found
            if (!$targetChatId && !empty($settings['telegram_chat_id'])) {
                $targetChatId = $settings['telegram_chat_id'];
            }

            if ($targetChatId) {
                $url = "https://api.telegram.org/bot{$token}/sendMessage?chat_id={$targetChatId}&text=" . urlencode($message);
                @file_get_contents($url);
            }
        }

        return true;
    }

    public function getForUser(int $userId): array {
        $notifications = $this->notificationStore->read();
        return array_values(array_filter($notifications, fn($n) => $n['user_id'] === $userId));
    }

    public function markAsRead(int $userId): void {
        $notifications = $this->notificationStore->read();
        foreach ($notifications as &$n) {
            if ($n['user_id'] === $userId) {
                $n['read'] = true;
            }
        }
        $this->notificationStore->save($notifications);
    }
}
