<?php
namespace Sanatorium\Core\Database;

class JsonStore {
    private $dataDir;

    public function __construct($dataDir) {
        $this->dataDir = rtrim($dataDir, '/') . '/';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }

    private function getFilePath($table) {
        return $this->dataDir . $table . '.json';
    }

    public function findAll($table) {
        $filePath = $this->getFilePath($table);
        if (!file_exists($filePath)) {
            return [];
        }
        $content = file_get_contents($filePath);
        return json_decode($content, true) ?: [];
    }

    public function findOne($table, $id) {
        $data = $this->findAll($table);
        foreach ($data as $item) {
            if (isset($item['id']) && $item['id'] == $id) {
                return $item;
            }
        }
        return null;
    }

    public function save($table, $data) {
        $filePath = $this->getFilePath($table);
        $allData = $this->findAll($table);

        if (isset($data['id'])) {
            $found = false;
            foreach ($allData as &$item) {
                if ($item['id'] == $data['id']) {
                    $item = array_merge($item, $data);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $allData[] = $data;
            }
        } else {
            $maxId = 0;
            foreach ($allData as $item) {
                if (isset($item['id']) && $item['id'] > $maxId) {
                    $maxId = $item['id'];
                }
            }
            $data['id'] = $maxId + 1;
            $allData[] = $data;
        }

        return $this->write($table, $allData) ? $data['id'] : false;
    }

    public function delete($table, $id) {
        $allData = $this->findAll($table);
        $filteredData = array_filter($allData, function($item) use ($id) {
            return !isset($item['id']) || $item['id'] != $id;
        });

        if (count($allData) === count($filteredData)) {
            return false;
        }

        return $this->write($table, array_values($filteredData));
    }

    private function write($table, $data) {
        $filePath = $this->getFilePath($table);
        $fp = fopen($filePath, 'w');
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        } else {
            fclose($fp);
            return false;
        }
    }
}
