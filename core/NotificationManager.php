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
                $this->rawSend($token, $targetChatId, $message);
            }
        }

        return true;
    }

    public function testConnection(string $token, string $chatId): array {
        $result = $this->rawSend($token, $chatId, "🧪 Тестовое сообщение системы ХОП. Если вы это видите, интеграция настроена верно!");
        return $result;
    }

    private function rawSend(string $token, string $chatId, string $message): array {
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'error' => $error];
        }

        $responseData = json_decode($response, true);
        if ($httpCode !== 200 || !($responseData['ok'] ?? false)) {
            return [
                'success' => false,
                'error' => $responseData['description'] ?? "HTTP Error $httpCode"
            ];
        }

        return ['success' => true];
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
