<?php

class Notifier {
    private $settings;

    public function __construct($settings = []) {
        $this->settings = $settings;
    }

    public function notifyStatusChange($task, $newStatus) {
        $message = "🔔 Task status changed!\n";
        $message .= "ID: {$task['id']}\n";
        $message .= "Title: {$task['title']}\n";
        $message .= "New status: " . strtoupper($newStatus);

        $this->sendTelegram($message);
        $this->sendEmail($message);
    }

    private function sendTelegram($message) {
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
        curl_exec($ch);
        curl_close($ch);
    }

    private function sendEmail($message) {
        if (empty($this->settings['admin_email'])) return;
        // mail($this->settings['admin_email'], "CRM Notification", $message);
    }
}
