<?php
declare(strict_types=1);

interface StorageInterface {
    public function getAll(string $collection): array;
    public function findById(string $collection, $id): ?array;
    public function findBy(string $collection, array $criteria): array;
    public function findOneBy(string $collection, array $criteria): ?array;
    public function insert(string $collection, array $data): array;
    public function update(string $collection, $id, array $data): ?array;
    public function delete(string $collection, $id): bool;
    public function testConnection(): bool;
    public function getDriverName(): string;
}

class StorageFactory {
    private static ?StorageInterface $instance = null;

    public static function getStorage(?string $overrideMode = null): StorageInterface {
        if (self::$instance !== null && $overrideMode === null) {
            return self::$instance;
        }

        $settingsFile = __DIR__ . '/../data/settings.json';
        $settings = [];
        if (file_exists($settingsFile)) {
            $settings = json_decode((string)file_get_contents($settingsFile), true) ?: [];
        }

        $mode = $overrideMode ?? ($settings['system']['storage_mode'] ?? 'json');

        if ($mode === 'mysql') {
            require_once __DIR__ . '/drivers/MysqlDriver.php';
            $mysqlConfig = $settings['mysql_config'] ?? [];
            try {
                $driver = new MysqlDriver($mysqlConfig);
                if ($overrideMode === null) {
                    self::$instance = $driver;
                }
                return $driver;
            } catch (Throwable $e) {
                error_log("MySQL connection error: " . $e->getMessage() . ", falling back to JSON driver.");
                require_once __DIR__ . '/drivers/JsonDriver.php';
                $driver = new JsonDriver(__DIR__ . '/../data');
                if ($overrideMode === null) {
                    self::$instance = $driver;
                }
                return $driver;
            }
        }

        require_once __DIR__ . '/drivers/JsonDriver.php';
        $driver = new JsonDriver(__DIR__ . '/../data');
        if ($overrideMode === null) {
            self::$instance = $driver;
        }
        return $driver;
    }

    public static function reset(): void {
        self::$instance = null;
    }
}
