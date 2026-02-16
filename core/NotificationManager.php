<?php
namespace Hop\Core;

class NotificationManager {
    private JsonStore $settingsStore;
    private JsonStore $notificationStore;

    public function __construct(JsonStore $settingsStore, JsonStore $notificationStore) {
        $this->settingsStore = $settingsStore;
        $this->notificationStore = $notificationStore;
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

        $this->notificationStore->write($notifications);

        // Telegram Mock
        $settings = $this->settingsStore->read();
        if (!empty($settings['telegram_token']) && !empty($settings['telegram_chat_id'])) {
            // Here we would call file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chatId}&text=" . urlencode($message));
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
        $this->notificationStore->write($notifications);
    }
}
