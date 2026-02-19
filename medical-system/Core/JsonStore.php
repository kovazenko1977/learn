<?php

namespace Medical\Core;

class JsonStore {
    private $filePath;
    private $sqlStore = null;
    private $filename;

    public function __construct($filename) {
        $this->filename = $filename;
        $dir = __DIR__ . '/../data/';

        // Check for MySQL driver in settings
        // To avoid infinite recursion, only check if we are NOT loading settings
        if ($filename !== 'settings') {
            $settingsStore = new JsonStore('settings');
            $settings = $settingsStore->getAll();
            if (($settings['db_driver'] ?? 'json') === 'mysql') {
                try {
                    $this->sqlStore = new MySqlStore($filename);
                    return;
                } catch (\Exception $e) {
                    // Fallback to JSON if MySQL fails
                }
            }
        }

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
            file_put_contents($dir . '.htaccess', 'Deny from all');
        }
        $this->filePath = $dir . $filename . '.json';
        if (!file_exists($this->filePath)) {
            $this->save([]);
        }
    }

    public function getAll() {
        if ($this->sqlStore) return $this->sqlStore->getAll();
        if (!file_exists($this->filePath)) {
            return [];
        }
        $content = file_get_contents($this->filePath);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    public function save($data) {
        if ($this->sqlStore) return $this->sqlStore->save($data);
        $fp = @fopen($this->filePath, 'w');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                fflush($fp);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
            return true;
        }
        return false;
    }

    public function findById($id, $key = 'id') {
        if ($this->sqlStore) return $this->sqlStore->findById($id, $key);
        $data = $this->getAll();
        foreach ($data as $item) {
            if (isset($item[$key]) && $item[$key] == $id) {
                return $item;
            }
        }
        return null;
    }

    public function updateById($id, $newData, $key = 'id') {
        if ($this->sqlStore) return $this->sqlStore->updateById($id, $newData, $key);
        $data = $this->getAll();
        $found = false;
        foreach ($data as &$item) {
            if (isset($item[$key]) && $item[$key] == $id) {
                $item = array_merge($item, $newData);
                $found = true;
                break;
            }
        }
        if ($found) {
            $this->save($data);
        }
        return $found;
    }

    public function deleteById($id, $key = 'id') {
        if ($this->sqlStore) return $this->sqlStore->deleteById($id, $key);
        $data = $this->getAll();
        $filteredData = array_filter($data, function($item) use ($id, $key) {
            return !(isset($item[$key]) && $item[$key] == $id);
        });
        if (count($data) !== count($filteredData)) {
            $this->save(array_values($filteredData));
            return true;
        }
        return false;
    }

    public function add($item) {
        if ($this->sqlStore) return $this->sqlStore->add($item);
        $data = $this->getAll();
        $data[] = $item;
        $this->save($data);
    }
}
