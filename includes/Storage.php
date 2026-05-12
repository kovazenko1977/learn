<?php

class Storage {
    private static $baseDir = __DIR__ . '/../data/';

    public static function read($filename) {
        $path = self::$baseDir . $filename;
        if (!file_exists($path)) {
            return null;
        }
        $content = file_get_contents($path);
        return json_decode($content, true);
    }

    public static function write($filename, $data) {
        $path = self::$baseDir . $filename;
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }
        return file_put_contents($path, $json, LOCK_EX) !== false;
    }

    public static function getSettings() {
        return self::read('settings.json');
    }

    public static function getPrices() {
        return self::read('prices.json') ?: [];
    }

    public static function savePrices($prices) {
        return self::write('prices.json', $prices);
    }
}
