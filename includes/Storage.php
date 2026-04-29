<?php
class Storage {
    private $dataDir;

    public function __construct($dataDir) {
        $this->dataDir = $dataDir;
        if (!file_exists($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
    }

    public function readCollection($collection) {
        $file = $this->dataDir . '/' . $collection . '.json';
        if (!file_exists($file)) return [];

        $fp = fopen($file, 'r');
        if (!$fp) return [];

        flock($fp, LOCK_SH);
        $data = file_get_contents($file);
        flock($fp, LOCK_UN);
        fclose($fp);

        return json_decode($data, true) ?: [];
    }

    public function writeCollection($collection, $data) {
        $file = $this->dataDir . '/' . $collection . '.json';

        $fp = fopen($file, 'w');
        if (!$fp) return false;

        flock($fp, LOCK_EX);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }

    public function findOne($collection, $query) {
        $items = $this->readCollection($collection);
        foreach ($items as $item) {
            $match = true;
            foreach ($query as $key => $value) {
                if (!isset($item[$key]) || $item[$key] != $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) return $item;
        }
        return null;
    }

    public function find($collection, $query) {
        $items = $this->readCollection($collection);
        $result = [];
        foreach ($items as $item) {
            $match = true;
            foreach ($query as $key => $value) {
                if (!isset($item[$key]) || $item[$key] != $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) $result[] = $item;
        }
        return $result;
    }

    public function insert($collection, $item) {
        $items = $this->readCollection($collection);
        $item['id'] = time() . rand(100, 999);
        $items[] = $item;
        $this->writeCollection($collection, $items);
        return $item;
    }

    public function update($collection, $id, $updates) {
        $items = $this->readCollection($collection);
        foreach ($items as &$item) {
            if ($item['id'] == $id) {
                $item = array_merge($item, $updates);
                $this->writeCollection($collection, $items);
                return $item;
            }
        }
        return null;
    }
}
