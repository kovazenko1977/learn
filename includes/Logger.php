<?php

class Logger {
    private $logFile;

    public function __construct() {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
            file_put_contents($logDir . '/.htaccess', "Deny from all");
        }
        $this->logFile = $logDir . '/actions.log';
    }

    public function log($userId, $action, $details = '') {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $entry = "[$timestamp] [User ID: $userId] [IP: $ip] [Action: $action] $details" . PHP_EOL;
        file_put_contents($this->logFile, $entry, FILE_APPEND);
    }
}
