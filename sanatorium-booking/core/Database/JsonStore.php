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

        $fp = @fopen($filePath, 'rb');
        if (!$fp) {
            return [];
        }

        flock($fp, LOCK_SH);
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
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

        // Ensure file exists
        if (!file_exists($filePath)) {
            file_put_contents($filePath, '[]');
        }

        $fp = fopen($filePath, 'c+b');
        if (!$fp) return false;

        if (flock($fp, LOCK_EX)) {
            $content = stream_get_contents($fp);
            $allData = json_decode($content, true) ?: [];

            if (isset($data['id'])) {
                $found = false;
                foreach ($allData as &$item) {
                    if ($item['id'] == $data['id']) {
                        $item = array_merge($item, $data);
                        $found = true;
                        break;
                    }
                }
                if (!$found) $allData[] = $data;
                $savedId = $data['id'];
            } else {
                $maxId = 0;
                foreach ($allData as $item) {
                    if (isset($item['id']) && $item['id'] > $maxId) $maxId = $item['id'];
                }
                $data['id'] = $maxId + 1;
                $allData[] = $data;
                $savedId = $data['id'];
            }

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($allData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            return $savedId;
        } else {
            fclose($fp);
            return false;
        }
    }

    public function delete($table, $id) {
        $filePath = $this->getFilePath($table);
        if (!file_exists($filePath)) return false;

        $fp = fopen($filePath, 'c+b');
        if (!$fp) return false;

        if (flock($fp, LOCK_EX)) {
            $content = stream_get_contents($fp);
            $allData = json_decode($content, true) ?: [];
            $filteredData = array_values(array_filter($allData, function($item) use ($id) {
                return !isset($item['id']) || $item['id'] != $id;
            }));

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($filteredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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
