<?php

class Logger {
    private static $logPath = __DIR__ . '/../storage/logs/';

    public static function log($message, $level = 'info', $file = 'system.log') {
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
        $timestamp = date('Y-m-d H:i:s');
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';
        $logEntry = "[$timestamp] [$level] [User: $userId] $message" . PHP_EOL;
        file_put_contents(self::$logPath . $file, $logEntry, FILE_APPEND);
    }
}
