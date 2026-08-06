<?php
/**
 * Configuration manager for TravelLine Clone.
 */

class Config {
    private static $configFile = __DIR__ . '/../data/config.json';
    private static $defaults = [
        'storage_type' => 'json', // 'json' or 'sql'
        'sql_driver' => 'sqlite', // 'sqlite' or 'mysql'
        'mysql_host' => '127.0.0.1',
        'mysql_dbname' => 'travelline_clone',
        'mysql_user' => 'root',
        'mysql_pass' => '',
        'hotel_name' => 'TravelLine Resort & Spa',
        'hotel_address' => 'ул. Первомайская, 166, Йошкар-Ола',
        'hotel_phone' => '+7 (8362) 63-00-98',
        'hotel_email' => 'welcome@travelline-clone.ru'
    ];

    public static function init() {
        $dir = dirname(self::$configFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        // Protect data directory
        $htaccess = $dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Require all denied\n");
        }
    }

    public static function get($key = null) {
        self::init();
        if (!file_exists(self::$configFile)) {
            self::save(self::$defaults);
            return $key ? self::$defaults[$key] : self::$defaults;
        }

        $data = json_decode(file_get_contents(self::$configFile), true);
        if (!is_array($data)) {
            $data = self::$defaults;
        }

        // Merge defaults in case new keys are added
        $data = array_merge(self::$defaults, $data);

        if ($key) {
            return isset($data[$key]) ? $data[$key] : null;
        }
        return $data;
    }

    public static function save($settings) {
        self::init();
        $current = file_exists(self::$configFile) ? json_decode(file_get_contents(self::$configFile), true) : [];
        if (!is_array($current)) {
            $current = [];
        }
        $newSettings = array_merge(self::$defaults, $current, $settings);
        return file_put_contents(self::$configFile, json_encode($newSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }
}
