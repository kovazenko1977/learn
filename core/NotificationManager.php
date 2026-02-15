<?php
namespace Hop\Core;

class NotificationManager {
    private JsonStore $settingsStore;
    private string $logPath;

    public function __construct(JsonStore $settingsStore, string $logPath) {
        $this->settingsStore = $settingsStore;
        $this->logPath = $logPath;
    }

    public function send(int $userId, string $message, string $type = 'info'): bool {
        // Internal log
        $notifications = [];
        if (file_exists($this->logPath)) {
            $content = file_get_contents($this->logPath);
            $notifications = json_decode($content, true) ?: [];
        }

        $notifications[] = [
            'user_id' => $userId,
            'message' => $message,
            'type' => $type,
            'timestamp' => date('Y-m-d H:i:s'),
            'read' => false
        ];

        file_put_contents($this->logPath, json_encode($notifications, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Telegram Mock
        $settings = $this->settingsStore->read();
        if (!empty($settings['telegram_token']) && !empty($settings['telegram_chat_id'])) {
            // Here we would call file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chatId}&text=" . urlencode($message));
        }

        return true;
    }

    public function getForUser(int $userId): array {
        if (!file_exists($this->logPath)) return [];
        $content = file_get_contents($this->logPath);
        $notifications = json_decode($content, true) ?: [];
        return array_values(array_filter($notifications, fn($n) => $n['user_id'] === $userId));
    }
}
