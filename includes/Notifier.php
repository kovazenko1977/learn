<?php
require_once __DIR__ . '/Storage.php';

class Notifier {
    public static function notify($message, $type = 'info') {
        // Log to a file as a mock for Telegram/Email
        $logPath = __DIR__ . '/../data/notifications.log';
        $entry = date('Y-m-d H:i:s') . " [$type] " . $message . PHP_EOL;
        file_put_contents($logPath, $entry, FILE_APPEND | LOCK_EX);

        $settings = Storage::get('settings');
        if (!empty($settings['telegram_bot_token']) && !empty($settings['telegram_chat_id'])) {
            // Mock call to Telegram API
            // file_get_contents("https://api.telegram.org/bot{$settings['telegram_bot_token']}/sendMessage?chat_id={$settings['telegram_chat_id']}&text=" . urlencode($message));
        }
    }
}
