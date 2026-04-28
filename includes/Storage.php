<?php
class Storage {
    private static $baseDir = __DIR__ . '/../data/';

    public static function read($filename) {
        $path = self::$baseDir . $filename . '.json';
        if (!file_exists($path)) {
            return [];
        }
        return json_decode(file_get_contents($path), true) ?: [];
    }

    public static function write($filename, $data) {
        $path = self::$baseDir . $filename . '.json';
        if (!is_dir(self::$baseDir)) {
            mkdir(self::$baseDir, 0777, true);
        }
        return self::atomicWrite($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private static function atomicWrite($path, $content) {
        $temp = tempnam(dirname($path), 'tmp_');
        if (file_put_contents($temp, $content) !== false) {
            chmod($temp, 0666);
            if (rename($temp, $path)) {
                return true;
            }
        }
        @unlink($temp);
        return false;
    }

    public static function generateId() {
        return bin2hex(random_bytes(8));
    }
}
