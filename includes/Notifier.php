<?php

class Notifier {
    public static function send($message, $userId = null) {
        // Mock notification logic
        // In a real app, this would send Telegram/Email
        error_log("Notification: " . $message);
    }

    public static function notifyStatusChange($taskId, $oldStatus, $newStatus) {
        self::send("Task #$taskId status changed from $oldStatus to $newStatus");
    }
}
