<?php
require_once __DIR__ . '/Storage.php';

class Notifier {
    public static function notify($message) {
        $settings = Storage::read('settings');
        if (!empty($settings['telegram_bot_token']) && !empty($settings['telegram_chat_id'])) {
            self::sendTelegram($settings['telegram_bot_token'], $settings['telegram_chat_id'], $message);
        }
    }

    private static function sendTelegram($token, $chatId, $message) {
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $message
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        curl_close($ch);
    }
}
