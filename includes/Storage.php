<?php
/**
 * Storage.php - Потокобезопасная работа с JSON-файлами
 * Реализует атомарные операции чтения/записи для предотвращения повреждения данных.
 */
class Storage {
    private static $baseDir = __DIR__ . '/../data/';

    /**
     * Инициализация хранилища: создание директории и защита .htaccess
     */
    public static function init() {
        if (!is_dir(self::$baseDir)) {
            if (!mkdir(self::$baseDir, 0777, true)) {
                error_log("Failed to create data directory: " . self::$baseDir);
                return false;
            }
        }
        $htaccess = self::$baseDir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all");
        }
        return true;
    }

    /**
     * Чтение данных из JSON файла с использованием разделяемой блокировки
     */
    public static function read(string $filename): array {
        $path = self::$baseDir . $filename . '.json';
        if (!file_exists($path)) return [];

        $fp = fopen($path, 'r');
        if (!$fp) return [];

        flock($fp, LOCK_SH);
        $content = '';
        while (!feof($fp)) {
            $content .= fread($fp, 8192);
        }
        flock($fp, LOCK_UN);
        fclose($fp);

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Запись данных в JSON файл с использованием эксклюзивной блокировки
     */
    public static function write(string $filename, array $data): bool {
        if (!self::init()) return false;

        $path = self::$baseDir . $filename . '.json';
        $fp = fopen($path, 'c+');
        if (!$fp) return false;

        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            fwrite($fp, $json);
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        } else {
            fclose($fp);
            return false;
        }
    }

    /**
     * Удаление файла из хранилища
     */
    public static function delete(string $filename): bool {
        $path = self::$baseDir . $filename . '.json';
        if (file_exists($path)) {
            return unlink($path);
        }
        return true;
    }
}
