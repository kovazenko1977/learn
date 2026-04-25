<?php

class Notifier {
    public static function send($userId, $message) {
        $user = Storage::getById('users.json', $userId);
        if (!$user) return;

        if (!empty($user['telegram_id'])) {
            self::sendTelegram($user['telegram_id'], $message);
        }

        if (!empty($user['email'])) {
            self::sendEmail($user['email'], 'CRM Notification', $message);
        }
    }

    public static function sendTelegram($chatId, $message) {
        $settings = Storage::read('settings.json');
        $botToken = $settings['telegram_bot_token'] ?? null;
        if (!$botToken) return;

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $message
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        curl_close($ch);
    }

    public static function sendEmail($to, $subject, $message) {
        // Basic mail() implementation. In a real scenario, use PHPMailer/SMTP.
        $headers = "From: crm@example.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        @mail($to, $subject, $message, $headers);
    }
}
