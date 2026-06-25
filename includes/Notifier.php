<?php

class Notifier {
    public static function notify($type, $task, $user = null) {
        $settingsPath = __DIR__ . '/../data/settings.json';
        if (!file_exists($settingsPath)) return;
        $settings = json_decode(file_get_contents($settingsPath), true);
        $notif = $settings['notifications'] ?? [];

        if (empty($notif['triggers'][$type])) return;

        $message = "Уведомление CRM [$type]\n";
        $message .= "Задача #{$task['id']}: {$task['title']}\n";
        $message .= "Статус: {$task['status']}\n";
        if ($user) $message .= "Исполнитель: {$user['full_name']}\n";

        if ($notif['email']) {
            $email = $user['email'] ?? ($settings['admin_email'] ?? 'admin@example.com');
            error_log("SENDING EMAIL to $email: $message");
        }

        if ($notif['telegram'] && !empty($user['telegram_id'])) {
            error_log("SENDING TELEGRAM to {$user['telegram_id']}: $message");
        }
    }
}
