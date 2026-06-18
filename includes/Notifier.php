<?php

class Notifier {
    private $settings;

    public function __construct($settings = []) {
        $this->settings = $settings;
    }

    public function notifyStatusChange($task, $newStatus) {
        $message = "🔔 Статус заявки изменен!\n";
        $message .= "ID: {$task['id']}\n";
        $message .= "Заголовок: {$task['title']}\n";
        $message .= "Новый статус: " . mb_strtoupper($newStatus);

        $this->sendTelegram($message);
        $this->sendEmail($message);
    }

    private function sendTelegram($message) {
        if (!($this->settings['notifications_telegram'] ?? false)) return;
        if (empty($this->settings['telegram_bot_token']) || empty($this->settings['telegram_chat_id'])) return;

        $url = "https://api.telegram.org/bot{$this->settings['telegram_bot_token']}/sendMessage";
        $data = [
            'chat_id' => $this->settings['telegram_chat_id'],
            'text' => $message
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_exec($ch);
        curl_close($ch);
    }

    private function sendEmail($message) {
        if (!($this->settings['notifications_email'] ?? false)) return;
        if (empty($this->settings['admin_email'])) return;
        $headers = "From: crm@example.com\r\n";
        $headers .= "Reply-To: crm@example.com\r\n";
        $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        @mail($this->settings['admin_email'], "CRM Уведомление", $message, $headers);
    }
}
