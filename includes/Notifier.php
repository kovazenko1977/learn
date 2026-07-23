<?php

class Notifier {
    private $config;

    public function __construct($settings) {
        $this->config = $settings['notifications'] ?? [];
    }

    public function notify($userId, $title, $message, $trigger = 'task_update') {
        // In a real system, this would lookup user's contact info and send via API.
        // For Sanatorium 2.0 simulation, we log notifications to a file.

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'trigger' => $trigger,
            'channels' => []
        ];

        if ($this->config['email_enabled'] ?? false) $logEntry['channels'][] = 'Email';
        if ($this->config['telegram_enabled'] ?? false) $logEntry['channels'][] = 'Telegram';
        if ($this->config['push_enabled'] ?? false) $logEntry['channels'][] = 'Push';

        $logFile = __DIR__ . '/../data/notifications.log';
        file_put_contents($logFile, json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);

        return true;
    }
}
