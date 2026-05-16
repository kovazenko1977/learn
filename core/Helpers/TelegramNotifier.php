<?php
namespace Sanatorium\Core\Helpers;

class TelegramNotifier {
    private $botToken;
    private $chatId;
    private $enabled;

    public function __construct() {
        $settingsPath = __DIR__ . '/../../data/settings.json';
        if (file_exists($settingsPath)) {
            $settings = json_decode(file_get_contents($settingsPath), true);
            $this->botToken = $settings['telegram']['bot_token'] ?? '';
            $this->chatId = $settings['telegram']['chat_id'] ?? '';
            $this->enabled = $settings['telegram']['enabled'] ?? false;
        }
    }

    public function sendMessage($message) {
        if (!$this->enabled || empty($this->botToken) || empty($this->chatId)) {
            return false;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        $data = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'ignore_errors' => true
            ]
        ];

        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        return $result !== false;
    }
}
