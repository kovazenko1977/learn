<?php
namespace Core;

class JsonStore {
    private $filePath;

    public function __construct($collection) {
        $this->filePath = __DIR__ . '/../Data/' . $collection . '.json';
        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, json_encode([]));
        }
    }

    public function findAll() {
        $fp = fopen($this->filePath, 'r');
        flock($fp, LOCK_SH);
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return json_decode($content, true) ?: [];
    }

    public function save($data) {
        $fp = fopen($this->filePath, 'c+');
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    public function findOne($id) {
        $data = $this->findAll();
        foreach ($data as $item) {
            if ($item['id'] == $id) return $item;
        }
        return null;
    }

    public function create($item) {
        $data = $this->findAll();
        $item['id'] = uniqid();
        $data[] = $item;
        $this->save($data);
        return $item;
    }

    public function update($id, $updates) {
        $data = $this->findAll();
        foreach ($data as &$item) {
            if ($item['id'] == $id) {
                $item = array_merge($item, $updates);
                $this->save($data);
                return $item;
            }
        }
        return null;
    }
}
