<?php

class Notifier {
    public static function notify($type, $task, $user = null) {
        $settings = json_decode(file_get_contents(__DIR__ . '/../data/settings.json'), true);
        $notif = $settings['notifications'] ?? [];

        $message = "Задача #{$task['id']}: {$task['title']}\nСтатус: {$task['status']}\n";
        if ($user) $message .= "Исполнитель: {$user['full_name']}\n";

        if ($notif['email']) {
            // mail($task_creator_email, "CRM Update", $message);
            error_log("EMAIL NOTIFICATION: $message");
        }

        if ($notif['telegram'] && !empty($user['telegram_id'])) {
            // Use bot API to send $message to $user['telegram_id']
            error_log("TELEGRAM NOTIFICATION to {$user['telegram_id']}: $message");
        }
    }
}
