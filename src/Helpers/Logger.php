<?php
namespace App\Helpers;

class Logger {
    private $logFile;

    public function __construct($logDir) {
        $this->logFile = rtrim($logDir, '/') . '/system.log';
    }

    public function log($message, $level = 'INFO') {
        $date = date('Y-m-d H:i:s');
        $formatted = "[$date] [$level] $message" . PHP_EOL;
        file_put_contents($this->logFile, $formatted, FILE_APPEND);
    }

    public function error($message) {
        $this->log($message, 'ERROR');
    }
}
