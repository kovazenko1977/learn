<?php

class Storage {
    private static $basePath = __DIR__ . '/../data/';

    public static function read($filename) {
        $path = self::$basePath . $filename;
        if (!file_exists($path)) {
            return [];
        }

        $fp = fopen($path, 'r');
        if (!$fp) return [];

        flock($fp, LOCK_SH);
        $content = file_get_contents($path);
        flock($fp, LOCK_UN);
        fclose($fp);

        return json_decode($content, true) ?: [];
    }

    public static function write($filename, $data) {
        $path = self::$basePath . $filename;
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return false;
        }

        $fp = fopen($path, 'c+');
        if (!$fp) return false;

        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $json);
            fflush($fp);
            flock($fp, LOCK_UN);
            $success = true;
        } else {
            $success = false;
        }

        fclose($fp);
        return $success;
    }
}
