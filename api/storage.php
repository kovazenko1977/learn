<?php
// api/storage.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/drivers/JsonDriver.php';
require_once __DIR__ . '/drivers/MysqlDriver.php';

class Storage {
    private static $driverInstance = null;

    public static function getDriver() {
        if (self::$driverInstance === null) {
            $driverType = defined('STORAGE_DRIVER') ? STORAGE_DRIVER : 'json';
            $mysqlConfig = [
                'host' => defined('DB_HOST') ? DB_HOST : '127.0.0.1',
                'port' => defined('DB_PORT') ? DB_PORT : 3306,
                'name' => defined('DB_NAME') ? DB_NAME : 'flower_studio',
                'user' => defined('DB_USER') ? DB_USER : 'root',
                'pass' => defined('DB_PASS') ? DB_PASS : ''
            ];

            $settingsFile = DATA_DIR . '/settings.json';
            if (file_exists($settingsFile)) {
                $raw = file_get_contents($settingsFile);
                $settingsList = json_decode($raw, true);
                $s = is_array($settingsList) ? (array_values($settingsList)[0] ?? []) : [];
                if (!empty($s['db_driver'])) {
                    $driverType = $s['db_driver'];
                }
                if (!empty($s['mysql_host'])) {
                    $mysqlConfig['host'] = $s['mysql_host'];
                    $mysqlConfig['name'] = $s['mysql_name'] ?? $mysqlConfig['name'];
                    $mysqlConfig['user'] = $s['mysql_user'] ?? $mysqlConfig['user'];
                    $mysqlConfig['pass'] = $s['mysql_pass'] ?? $mysqlConfig['pass'];
                }
            }

            if ($driverType === 'mysql') {
                try {
                    $mysqlDriver = new MysqlDriver($mysqlConfig);
                    if ($mysqlDriver->testConnection()) {
                        self::$driverInstance = $mysqlDriver;
                    } else {
                        self::$driverInstance = new JsonDriver();
                    }
                } catch (\Throwable $e) {
                    self::$driverInstance = new JsonDriver();
                }
            } else {
                self::$driverInstance = new JsonDriver();
            }
        }
        return self::$driverInstance;
    }

    public static function getDriverType(): string {
        $driver = self::getDriver();
        return ($driver instanceof MysqlDriver) ? 'mysql' : 'json';
    }

    public static function getAll(string $collection): array {
        return self::getDriver()->getAll($collection);
    }

    public static function getById(string $collection, $id): ?array {
        return self::getDriver()->getById($collection, $id);
    }

    public static function findWhere(string $collection, array $criteria): array {
        return self::getDriver()->findWhere($collection, $criteria);
    }

    public static function insert(string $collection, array $data): array {
        return self::getDriver()->insert($collection, $data);
    }

    public static function update(string $collection, $id, array $data): ?array {
        return self::getDriver()->update($collection, $id, $data);
    }

    public static function delete(string $collection, $id): bool {
        return self::getDriver()->delete($collection, $id);
    }

    public static function saveAll(string $collection, array $items): bool {
        $driver = self::getDriver();
        if ($driver instanceof JsonDriver) {
            return $driver->saveAll($collection, $items);
        }
        return false;
    }
}
