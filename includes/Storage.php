<?php
class Storage {
    private $dataDir;

    public function __construct($dataDir) {
        $this->dataDir = realpath($dataDir);
        if (!$this->dataDir) {
            // Directory might not exist yet
            if (!mkdir($dataDir, 0755, true)) {
                die("Storage Error: Cannot create data directory.");
            }
            $this->dataDir = realpath($dataDir);
        }

        if (!is_writable($this->dataDir)) {
             @chmod($this->dataDir, 0755);
        }
    }

    public function readCollection($collection) {
        $file = $this->dataDir . '/' . $collection . '.json';
        if (!file_exists($file)) {
            return [];
        }

        $fp = fopen($file, 'r');
        if (!$fp) return [];

        flock($fp, LOCK_SH);
        $size = filesize($file);
        $data = $size > 0 ? fread($fp, $size) : '';
        flock($fp, LOCK_UN);
        fclose($fp);

        $data = str_replace("\xEF\xBB\xBF", '', $data);
        $decoded = json_decode($data, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function writeCollection($collection, $data) {
        $file = $this->dataDir . '/' . $collection . '.json';

        $fp = fopen($file, 'c');
        if (!$fp) return false;

        flock($fp, LOCK_EX);
        ftruncate($fp, 0);
        rewind($fp);
        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        fwrite($fp, $encoded);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        @chmod($file, 0644);
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
        if (!isset($item['id'])) {
            $item['id'] = time() . rand(100, 999);
        }
        $items[] = $item;
        $this->writeCollection($collection, $items);
        return $item;
    }

    public function update($collection, $id, $updates) {
        $items = $this->readCollection($collection);
        $updated = false;
        foreach ($items as &$item) {
            if ($item['id'] == $id) {
                $item = array_merge($item, $updates);
                $updated = true;
                break;
            }
        }
        if ($updated) {
            $this->writeCollection($collection, $items);
            return true;
        }
        return null;
    }
}
