<?php
class Storage {
    private $dataDir;

    public function __construct($dataDir) {
        $this->dataDir = realpath($dataDir);
        if (!$this->dataDir) {
            if (!mkdir($dataDir, 0755, true)) {
                die("Storage Error: Cannot create data directory.");
            }
            $this->dataDir = realpath($dataDir);
        }
    }

    public function readCollection($collection) {
        $file = $this->dataDir . '/' . $collection . '.json';
        if (!file_exists($file)) return [];
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

    private function write($collection, $data) {
        $file = $this->dataDir . '/' . $collection . '.json';
        $temp = $file . '.tmp';
        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($temp, $encoded, LOCK_EX) !== false) {
            rename($temp, $file);
            @chmod($file, 0644);
            return true;
        }
        return false;
    }

    /**
     * Perform an atomic operation on a collection with exclusive lock
     */
    public function transactional($collection, callable $callback) {
        $file = $this->dataDir . '/' . $collection . '.json';
        if (!file_exists($file)) touch($file);

        $fp = fopen($file, 'c+');
        if (!$fp) return false;

        flock($fp, LOCK_EX);

        $size = filesize($file);
        $content = $size > 0 ? fread($fp, $size) : '';
        $content = str_replace("\xEF\xBB\xBF", '', $content);
        $data = json_decode($content, true) ?: [];

        $result = $callback($data);

        if ($result !== false) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
        }

        flock($fp, LOCK_UN);
        fclose($fp);
        return $result;
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

    public function insert($collection, $item) {
        return $this->transactional($collection, function(&$items) use ($item) {
            if (!isset($item['id'])) $item['id'] = time() . rand(100, 999);
            $items[] = $item;
            return $item;
        });
    }

    public function update($collection, $id, $updates) {
        return $this->transactional($collection, function(&$items) use ($id, $updates) {
            foreach ($items as &$item) {
                if ($item['id'] == $id) {
                    $item = array_merge($item, $updates);
                    return true;
                }
            }
            return false;
        });
    }

    public function delete($collection, $id) {
        return $this->transactional($collection, function(&$items) use ($id) {
            $count = count($items);
            $items = array_values(array_filter($items, function($i) use ($id) {
                return $i['id'] != $id;
            }));
            return count($items) < $count;
        });
    }
}
