<?php
class Storage {
    private static $basePath = __DIR__ . '/../data/';

    public static function init() {
        if (!is_dir(self::$basePath)) {
            mkdir(self::$basePath, 0777, true);
        }
        if (!is_dir(__DIR__ . '/../uploads')) {
            mkdir(__DIR__ . '/../uploads', 0777, true);
        }
    }

    public static function read($file) {
        $path = self::$basePath . $file . '.json';
        if (!file_exists($path)) return [];

        $fp = fopen($path, 'r');
        flock($fp, LOCK_SH);
        $content = file_get_contents($path);
        flock($fp, LOCK_UN);
        fclose($fp);

        return json_decode($content, true) ?: [];
    }

    public static function write($file, $data) {
        $path = self::$basePath . $file . '.json';
        $fp = fopen($path, 'w+');
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }
}
Storage::init();
