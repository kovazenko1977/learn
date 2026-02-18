<?php

namespace Medical\Core;

class JsonStore {
    private $filePath;

    public function __construct($filename) {
        $this->filePath = __DIR__ . '/../data/' . $filename . '.json';
        if (!file_exists($this->filePath)) {
            $this->save([]);
        }
    }

    public function getAll() {
        if (!file_exists($this->filePath)) {
            return [];
        }
        $content = file_get_contents($this->filePath);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    public function save($data) {
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
        $data = $this->getAll();
        foreach ($data as $item) {
            if (isset($item[$key]) && $item[$key] == $id) {
                return $item;
            }
        }
        return null;
    }

    public function updateById($id, $newData, $key = 'id') {
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
        $data = $this->getAll();
        $data[] = $item;
        $this->save($data);
    }
}
