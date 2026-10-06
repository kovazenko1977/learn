<?php
// api/storage.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/drivers/JsonDriver.php';
require_once __DIR__ . '/drivers/MysqlDriver.php';

class Storage {
    private static $instance = null;
    private $driver;
    private $driverType = 'json';

    private function __construct() {
        $this->initDriver();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function initDriver() {
        $settingsFile = DATA_DIR . '/settings.json';
        $driverType = 'json';
        $mysqlConfig = null;

        if (file_exists($settingsFile)) {
            $content = file_get_contents($settingsFile);
            $settingsList = json_decode($content, true);
            if (is_array($settingsList) && !empty($settingsList)) {
                $setting = $settingsList[0];
                $driverType = $setting['driver'] ?? 'json';
                $mysqlConfig = $setting['mysql_config'] ?? null;
            }
        }

        if ($driverType === 'mysql' && is_array($mysqlConfig)) {
            try {
                $this->driver = new MysqlDriver(
                    $mysqlConfig['host'] ?? '127.0.0.1',
                    $mysqlConfig['dbname'] ?? 'flower_studio',
                    $mysqlConfig['user'] ?? 'root',
                    $mysqlConfig['password'] ?? '',
                    $mysqlConfig['port'] ?? 3306
                );
                $this->driverType = 'mysql';
                return;
            } catch (Exception $e) {
                // Fallback to JSON if MySQL connection fails
                $this->driver = new JsonDriver();
                $this->driverType = 'json';
                return;
            }
        }

        $this->driver = new JsonDriver();
        $this->driverType = 'json';
    }

    public function getDriverType() {
        return $this->driverType;
    }

    public function all($entity) {
        return $this->driver->all($entity);
    }

    public function get($entity, array $filter = []) {
        return $this->driver->get($entity, $filter);
    }

    public function getById($entity, $id) {
        return $this->driver->getById($entity, $id);
    }

    public function insert($entity, array $data) {
        return $this->driver->insert($entity, $data);
    }

    public function update($entity, $id, array $data) {
        return $this->driver->update($entity, $id, $data);
    }

    public function delete($entity, $id) {
        return $this->driver->delete($entity, $id);
    }
}
