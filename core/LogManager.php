<?php
namespace Hop\Core;

class LogManager {
    private string $logDir;

    public function __construct(string $logDir = 'data/logs/') {
        $this->logDir = rtrim($logDir, '/') . '/';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public function log(string $action, int $userId, string $details = '', string $level = 'info'): void {
        $date = date('Y-m-d');
        $logFile = $this->logDir . $date . '.json';

        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'level' => $level,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ];

        $logs = [];
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            $logs = json_decode($content, true) ?: [];
        }

        $logs[] = $entry;
        file_put_contents($logFile, json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function getLogs(string $date = null): array {
        if (!$date) $date = date('Y-m-d');
        $logFile = $this->logDir . $date . '.json';

        if (file_exists($logFile)) {
            return json_decode(file_get_contents($logFile), true) ?: [];
        }
        return [];
    }

    public function getLogFiles(): array {
        $files = glob($this->logDir . '*.json');
        return array_map(fn($f) => basename($f, '.json'), $files);
    }
}
