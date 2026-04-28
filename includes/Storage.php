<?php
class Storage {
    private static $settingsFile = __DIR__ . '/../data/settings.json';
    private static $pdo = null;

    public static function getSettings() {
        if (!file_exists(self::$settingsFile)) return [];
        return json_decode(file_get_contents(self::$settingsFile), true);
    }

    public static function saveSettings($data) {
        return self::atomicWrite(self::$settingsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public static function isMySQL() {
        $s = self::getSettings();
        return isset($s['storage_mode']) && $s['storage_mode'] === 'mysql';
    }

    public static function getPDO() {
        if (self::$pdo !== null) return self::$pdo;
        $s = self::getSettings();
        $c = $s['mysql_config'] ?? null;
        if (!$c || empty($c['host'])) return null;

        try {
            $dsn = "mysql:host={$c['host']};dbname={$c['db']};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            return self::$pdo;
        } catch (Exception $e) {
            return null;
        }
    }

    public static function getData($filename) {
        if (self::isMySQL()) {
            $pdo = self::getPDO();
            if (!$pdo) return [];
            try {
                $stmt = $pdo->query("SELECT * FROM {$filename}");
                $rows = $stmt->fetchAll();
                foreach ($rows as &$row) {
                    if (isset($row['comments'])) $row['comments'] = json_decode($row['comments'], true);
                    if (isset($row['attachments'])) $row['attachments'] = json_decode($row['attachments'], true);
                }
                return $rows;
            } catch (Exception $e) {
                return [];
            }
        }
        $path = __DIR__ . "/../data/{$filename}.json";
        if (!file_exists($path)) return [];
        return json_decode(file_get_contents($path), true);
    }

    public static function saveData($filename, $data) {
        if (self::isMySQL()) {
            $pdo = self::getPDO();
            if (!$pdo) return false;
            self::initializeMySQL();

            $pdo->exec("DELETE FROM {$filename}");
            if (empty($data)) return true;

            $first = $data[0];
            $cols = array_keys($first);
            $placeholders = implode(',', array_fill(0, count($cols), '?'));
            $sql = "INSERT INTO {$filename} (" . implode(',', $cols) . ") VALUES ({$placeholders})";
            $stmt = $pdo->prepare($sql);

            foreach ($data as $row) {
                $values = [];
                foreach ($cols as $col) {
                    $val = $row[$col] ?? null;
                    if (is_array($val)) $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                    $values[] = $val;
                }
                $stmt->execute($values);
            }
            return true;
        }
        $path = __DIR__ . "/../data/{$filename}.json";
        return self::atomicWrite($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public static function initializeMySQL() {
        $pdo = self::getPDO();
        if (!$pdo) return;

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id VARCHAR(50) PRIMARY KEY,
            username VARCHAR(50) UNIQUE,
            password_hash VARCHAR(255),
            full_name VARCHAR(100),
            role VARCHAR(50),
            department VARCHAR(100),
            created_at DATETIME
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
            id VARCHAR(50) PRIMARY KEY,
            title VARCHAR(255),
            category VARCHAR(100),
            priority VARCHAR(50),
            description TEXT,
            location VARCHAR(255),
            status VARCHAR(50),
            created_by VARCHAR(50),
            created_by_name VARCHAR(100),
            department VARCHAR(100),
            executor_id VARCHAR(50),
            executor_name VARCHAR(100),
            created_at DATETIME,
            deadline DATETIME,
            comments LONGTEXT,
            attachments LONGTEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function atomicWrite($filepath, $content) {
        $temp = tempnam(dirname($filepath), 'tmp');
        if (file_put_contents($temp, $content) !== false) {
            if (rename($temp, $filepath)) {
                chmod($filepath, 0664);
                return true;
            }
        }
        @unlink($temp);
        return false;
    }
}