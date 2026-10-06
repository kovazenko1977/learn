<?php
// api/drivers/JsonDriver.php

require_once __DIR__ . '/../config.php';

class JsonDriver {
    private $dataDir;

    public function __construct($dataDir = DATA_DIR) {
        $this->dataDir = $dataDir;
        if (!file_exists($this->dataDir)) {
            @mkdir($this->dataDir, 0777, true);
        }
    }

    private function getFilePath($entity) {
        $clean = preg_replace('/[^a-zA-Z0-9_]/', '', $entity);
        return $this->dataDir . '/' . $clean . '.json';
    }

    public function all($entity) {
        $filePath = $this->getFilePath($entity);
        if (!file_exists($filePath)) {
            return [];
        }
        $fp = @fopen($filePath, 'r');
        if (!$fp) {
            return [];
        }
        flock($fp, LOCK_SH);
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function saveAll($entity, array $items) {
        $filePath = $this->getFilePath($entity);
        $fp = fopen($filePath, 'c+');
        if (!$fp) {
            return false;
        }
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        }
        fclose($fp);
        return false;
    }

    public function get($entity, array $filter = []) {
        $items = $this->all($entity);
        if (empty($filter)) {
            return $items;
        }
        return array_values(array_filter($items, function($item) use ($filter) {
            foreach ($filter as $key => $val) {
                if (!isset($item[$key]) || $item[$key] != $val) {
                    return false;
                }
            }
            return true;
        }));
    }

    public function getById($entity, $id) {
        $items = $this->all($entity);
        foreach ($items as $item) {
            if (isset($item['id']) && (string)$item['id'] === (string)$id) {
                return $item;
            }
        }
        return null;
    }

    public function insert($entity, array $data) {
        $items = $this->all($entity);
        if (!isset($data['id'])) {
            $data['id'] = 'id_' . uniqid() . '_' . rand(100, 999);
        }
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $items[] = $data;
        $this->saveAll($entity, $items);
        return $data;
    }

    public function update($entity, $id, array $data) {
        $items = $this->all($entity);
        $found = false;
        $updatedItem = null;
        foreach ($items as $index => $item) {
            if (isset($item['id']) && (string)$item['id'] === (string)$id) {
                $items[$index] = array_merge($item, $data, ['updated_at' => date('Y-m-d H:i:s')]);
                $updatedItem = $items[$index];
                $found = true;
                break;
            }
        }
        if ($found) {
            $this->saveAll($entity, $items);
            return $updatedItem;
        }
        return null;
    }

    public function delete($entity, $id) {
        $items = $this->all($entity);
        $newItems = [];
        $deleted = false;
        foreach ($items as $item) {
            if (isset($item['id']) && (string)$item['id'] === (string)$id) {
                $deleted = true;
            } else {
                $newItems[] = $item;
            }
        }
        if ($deleted) {
            $this->saveAll($entity, $newItems);
            return true;
        }
        return false;
    }
}
