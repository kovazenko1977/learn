<?php
class Storage {
    private static $dataDir = __DIR__ . '/../data/';

    public static function read($file) {
        $path = self::$dataDir . $file . '.json';
        if (!file_exists($path)) return [];
        $content = file_get_contents($path);
        return json_decode($content, true) ?: [];
    }

    public static function save($file, $data) {
        $path = self::$dataDir . $file . '.json';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            error_log("JSON encode error for $file: " . json_last_error_msg());
            return false;
        }
        return file_put_contents($path, $json, LOCK_EX) !== false;
    }

    public static function log($action, $user = 'system') {
        $logs = self::read('logs');
        $logs[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => $user,
            'action' => $action,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        // Keep last 1000 logs
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }
        self::save('logs', $logs);
    }

    public static function addPoints($userId, $points) {
        $users = self::read('users');
        foreach ($users as &$user) {
            if ($user['id'] === $userId) {
                $user['points'] = ($user['points'] ?? 0) + $points;
                break;
            }
        }
        return self::save('users', $users);
    }
}
