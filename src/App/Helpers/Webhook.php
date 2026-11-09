<?php

namespace App\Helpers;

class Webhook
{
    public static function send(string $url, array $newsItem, string $sectionName): void
    {
        if (empty($url)) return;

        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . "://" . $host . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/admin');

        $itemUrl = $baseUrl . "/?news_id=" . $newsItem['id'];

        $payload = [
            'content' => "📢 **Новая публикация в разделе $sectionName**",
            'embeds' => [[
                'title' => $newsItem['title'],
                'url' => $itemUrl,
                'description' => mb_substr(strip_tags($newsItem['content']), 0, 200) . '...',
                'color' => 5090558,
                'timestamp' => date('c'),
                'footer' => [
                    'text' => 'NewsManager'
                ]
            ]]
        ];

        // Generic payload if not Discord
        if (strpos($url, 'discord.com') === false) {
            $payload = [
                'text' => "Новая публикация: " . $newsItem['title'] . " - " . $itemUrl,
                'news' => $newsItem
            ];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);
    }
}
