<?php

class Storage {
    private static $basePath = __DIR__ . '/../data/';

    public static function read($filename) {
        $path = self::$basePath . $filename;
        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        return json_decode($content, true);
    }

    public static function write($filename, $data) {
        $path = self::$basePath . $filename;
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Atomic write
        $tmp = $path . '.tmp';
        if (file_put_contents($tmp, $json, LOCK_EX) !== false) {
            if (rename($tmp, $path)) {
                return true;
            }
        }
        return false;
    }
}
