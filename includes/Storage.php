<?php
class Storage {
    private static $dataDir = __DIR__ . '/../data/';

    public static function read($file) {
        if (self::isMysqlMode() && self::isTableExists($file)) {
            return self::readFromMysql($file);
        }
        $path = self::$dataDir . $file . '.json';
        if (!file_exists($path)) return [];
        $content = file_get_contents($path);
        return json_decode($content, true) ?: [];
    }

    public static function save($file, $data) {
        if (self::isMysqlMode()) {
            self::ensureTable($file, $data);
            return self::saveToMysql($file, $data);
        }
        $path = self::$dataDir . $file . '.json';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($path, $json, LOCK_EX) !== false;
    }

    private static function isMysqlMode() {
        $settings = self::readJsonDirect('settings');
        return !empty($settings['mysql_mode']) && !empty($settings['db_name']);
    }

    private static function getPdo() {
        static $pdo = null;
        if ($pdo) return $pdo;
        $s = self::readJsonDirect('settings');
        $dsn = "mysql:host={$s['db_host']};dbname={$s['db_name']};charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $s['db_user'], $s['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            return $pdo;
        } catch (Exception $e) {
            return null;
        }
    }

    private static function readJsonDirect($file) {
        $path = self::$dataDir . $file . '.json';
        if (!file_exists($path)) return [];
        return json_decode(file_get_contents($path), true) ?: [];
    }

    private static function isTableExists($table) {
        $pdo = self::getPdo();
        if (!$pdo) return false;
        try {
            $res = $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
            return $res !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    private static function ensureTable($table, $data) {
        if (empty($data)) return;
        if (self::isTableExists($table)) return;

        $pdo = self::getPdo();
        if (!$pdo) return;

        $first = is_array($data[0] ?? null) ? $data[0] : $data;
        $cols = [];
        foreach ($first as $k => $v) {
            $type = is_numeric($v) ? "INT" : "TEXT";
            if ($k === 'id') $type = "VARCHAR(255) PRIMARY KEY";
            $cols[] = "`$k` $type";
        }
        $sql = "CREATE TABLE IF NOT EXISTS `$table` (" . implode(', ', $cols) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sql);
    }

    private static function readFromMysql($table) {
        $pdo = self::getPdo();
        if (!$pdo) return [];
        return $pdo->query("SELECT * FROM `$table`")->fetchAll();
    }

    private static function saveToMysql($table, $data) {
        $pdo = self::getPdo();
        if (!$pdo) return false;
        $pdo->exec("DELETE FROM `$table`");
        if (empty($data)) return true;

        $keys = array_keys($data[0]);
        $fields = implode('`, `', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        $stmt = $pdo->prepare("INSERT INTO `$table` (`$fields`) VALUES ($placeholders)");

        foreach ($data as $row) {
            $values = array_values($row);
            foreach($values as &$v) if(is_array($v)) $v = json_encode($v);
            $stmt->execute($values);
        }
        return true;
    }

    public static function log($action, $user = 'system') {
        $logs = self::read('logs');
        $logs[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => $user,
            'action' => $action,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        if (count($logs) > 1000) $logs = array_slice($logs, -1000);
        self::save('logs', $logs);
    }
}
