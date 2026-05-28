<?php
namespace App\Models;

class JsonStore {
    private $storageFile;
    private $data;

    public function __construct($name) {
        $this->storageFile = __DIR__ . "/../../../data/$name.json";
        if (!file_exists(dirname($this->storageFile))) {
            mkdir(dirname($this->storageFile), 0755, true);
        }
        $this->load();
    }

    private function load() {
        if (file_exists($this->storageFile)) {
            $this->data = json_decode(file_get_contents($this->storageFile), true) ?: [];
        } else {
            $this->data = [];
        }
    }

    private function save() {
        file_put_contents($this->storageFile, json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function getAll() {
        return $this->data;
    }

    public function getById($id) {
        foreach ($this->data as $item) {
            if ($item['id'] == $id) return $item;
        }
        return null;
    }

    public function add($item) {
        $item['id'] = time() . rand(100, 999);
        $item['created_at'] = date('Y-m-d H:i:s');
        $this->data[] = $item;
        $this->save();
        return $item['id'];
    }

    public function update($id, $newData) {
        foreach ($this->data as &$item) {
            if ($item['id'] == $id) {
                $item = array_merge($item, $newData);
                $this->save();
                return true;
            }
        }
        return false;
    }

    public function delete($id) {
        $this->data = array_filter($this->data, fn($item) => $item['id'] != $id);
        $this->save();
    }
}
