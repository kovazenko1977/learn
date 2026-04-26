<?php
class Storage {
    private static $storageDir = __DIR__ . '/../data/';
    private static $pdo = null;

    private static function getMode() {
        $path = self::$storageDir . 'settings.json';
        if (!file_exists($path)) return 'json';
        $settings = json_decode(file_get_contents($path), true);
        return $settings['storage_mode'] ?? 'json';
    }

    private static function getPDO() {
        if (self::$pdo !== null) return self::$pdo;
        $settings = self::get('settings');
        $db = $settings['mysql'] ?? [];
        if (empty($db)) return null;

        $dsn = "mysql:host={$db['host']};dbname={$db['database']};charset=utf8mb4";
        try {
            self::$pdo = new PDO($dsn, $db['username'], $db['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            return self::$pdo;
        } catch (PDOException $e) {
            return null;
        }
    }

    public static function get($file) {
        if (self::getMode() === 'mysql' && $file !== 'settings') {
            $pdo = self::getPDO();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("SELECT data FROM generic_storage WHERE name = ?");
                    $stmt->execute([$file]);
                    $row = $stmt->fetch();
                    return $row ? json_decode($row['data'], true) : [];
                } catch (Exception $e) {
                    return [];
                }
            }
        }

        $path = self::$storageDir . $file . '.json';
        if (!file_exists($path)) return [];

        $fp = fopen($path, 'rb');
        if (!$fp) return [];
        flock($fp, LOCK_SH);
        $data = file_get_contents($path);
        flock($fp, LOCK_UN);
        fclose($fp);

        return json_decode($data, true) ?: [];
    }

    public static function set($file, $data) {
        if (self::getMode() === 'mysql' && $file !== 'settings') {
            $pdo = self::getPDO();
            if ($pdo) {
                // Ensure table exists
                $pdo->exec("CREATE TABLE IF NOT EXISTS generic_storage (name VARCHAR(255) PRIMARY KEY, data LONGTEXT)");
                $stmt = $pdo->prepare("INSERT INTO generic_storage (name, data) VALUES (?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data)");
                return $stmt->execute([$file, json_encode($data, JSON_UNESCAPED_UNICODE)]);
            }
        }

        if (!is_dir(self::$storageDir)) {
            mkdir(self::$storageDir, 0777, true);
        }
        $path = self::$storageDir . $file . '.json';

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $fp = fopen($path, 'cb');
        if (!$fp) return false;
        flock($fp, LOCK_EX);
        ftruncate($fp, 0);
        fwrite($fp, $json);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }

    public static function atomicWrite($file, $data) {
        return self::set($file, $data);
    }
}
