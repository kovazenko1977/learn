<?php
namespace Medical\Core\Managers;

use Medical\Core\JsonStore;
use Medical\Core\MySqlStore;
use Medical\Core\DB;

class MigrationManager {
    /**
     * Migrates data from one driver to another.
     * @param string $fromDriver 'json' or 'mysql'
     * @param string $toDriver 'json' or 'mysql'
     * @return bool
     */
    public function migrate($fromDriver, $toDriver) {
        if ($fromDriver === $toDriver) return true;

        $tables = ['staff', 'procedures_directory', 'patients', 'appointments', 'activity_log', 'templates'];

        foreach ($tables as $table) {
            $sourceData = $this->getData($table, $fromDriver);
            $this->clearTarget($table, $toDriver);
            $this->saveToTarget($table, $toDriver, $sourceData);
        }

        // Handle settings (merge organization details, keep target driver)
        $sourceSettings = $this->getData('settings', $fromDriver);
        $targetSettings = $this->getData('settings', $toDriver);

        // Keys to ignore from source (DB config)
        $ignoreKeys = ['db_driver', 'db_host', 'db_name', 'db_user', 'db_pass'];

        foreach ($sourceSettings as $key => $value) {
            if (!in_array($key, $ignoreKeys)) {
                $targetSettings[$key] = $value;
            }
        }

        $this->saveToTarget('settings', $toDriver, $targetSettings);

        return true;
    }

    private function getData($table, $driver) {
        if ($driver === 'mysql') {
            $store = new MySqlStore($table);
            return $store->getAll();
        } else {
            // Read directly from file to bypass global driver check
            $path = __DIR__ . '/../../data/' . $table . '.json';
            if (!file_exists($path)) return [];
            return json_decode(file_get_contents($path), true) ?: [];
        }
    }

    private function clearTarget($table, $driver) {
        if ($driver === 'mysql') {
            $pdo = DB::getInstance()->getPdo();
            $pdo->exec("DELETE FROM " . $table);
        } else {
            $path = __DIR__ . '/../../data/' . $table . '.json';
            file_put_contents($path, json_encode([]));
        }
    }

    private function saveToTarget($table, $driver, $data) {
        if ($driver === 'mysql') {
            $store = new MySqlStore($table);
            if ($table === 'settings' || $table === 'templates') {
                $store->save($data);
            } else {
                foreach ($data as $item) {
                    $store->add($item);
                }
            }
        } else {
            $path = __DIR__ . '/../../data/' . $table . '.json';
            file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}
