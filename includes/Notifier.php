<?php
class Notifier {
    public static function send($userId, $message) {
        $log = date('[Y-m-d H:i:s] ') . "User ID {$userId}: {$message}" . PHP_EOL;
        file_put_contents(__DIR__ . '/../data/notifications.log', $log, FILE_APPEND);

        // Mock Telegram/Email integration logic would go here
    }

    public static function notifyStatusChange($task, $newStatus) {
        $msg = "Статус вашей заявки #{$task['id']} изменен на: {$newStatus}";
        self::send($task['created_by'], $msg);

        if ($task['executor_id']) {
            $msgExecutor = "Статус назначенной вам задачи #{$task['id']} изменен на: {$newStatus}";
            self::send($task['executor_id'], $msgExecutor);
        }
    }
}