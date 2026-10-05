<?php
// api/storage.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/drivers/JsonDriver.php';
require_once __DIR__ . '/drivers/MysqlDriver.php';

class Storage {
    private static $driverInstance = null;

    public static function getDriver() {
        if (self::$driverInstance === null) {
            $settingsFile = DATA_DIR . '/settings.json';
            $driverType = 'json';
            $mysqlConfig = [];

            if (file_exists($settingsFile)) {
                $raw = file_get_contents($settingsFile);
                $settings = json_decode($raw, true);
                if (is_array($settings)) {
                    $driverType = $settings['db_driver'] ?? 'json';
                    $mysqlConfig = $settings['mysql_config'] ?? [];
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
