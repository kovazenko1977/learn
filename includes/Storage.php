<?php
require_once __DIR__ . '/Auth.php';

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

    public static function getData($filename) {
        if (self::isMySQL()) {
            return self::getMySQLData($filename);
        }
        $path = __DIR__ . "/../data/{$filename}.json";
        if (!file_exists($path)) return [];
        return json_decode(file_get_contents($path), true);
    }

    public static function saveData($filename, $data) {
        if (self::isMySQL()) {
            return self::saveMySQLData($filename, $data);
        }
        $path = __DIR__ . "/../data/{$filename}.json";
        return self::atomicWrite($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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

    private static function getMySQLData($table) {
        $pdo = self::getPDO();
        if (!$pdo) return [];
        try {
            $stmt = $pdo->query("SELECT data_json FROM system_data WHERE table_name = " . $pdo->quote($table));
            $row = $stmt->fetch();
            return $row ? json_decode($row['data_json'], true) : [];
        } catch (Exception $e) {
            self::initializeMySQL();
            return [];
        }
    }

    private static function saveMySQLData($table, $data) {
        $pdo = self::getPDO();
        if (!$pdo) return false;
        self::initializeMySQL();
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare("INSERT INTO system_data (table_name, data_json) VALUES (?, ?) ON DUPLICATE KEY UPDATE data_json = ?");
        return $stmt->execute([$table, $json, $json]);
    }

    public static function initializeMySQL() {
        $pdo = self::getPDO();
        if (!$pdo) return;
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_data (
            table_name VARCHAR(50) PRIMARY KEY,
            data_json LONGTEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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