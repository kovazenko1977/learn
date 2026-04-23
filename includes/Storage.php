<?php
class Storage {
    private $file;

    public function __construct($filename) {
        $this->file = __DIR__ . '/../data/' . $filename;
        if (!file_exists(dirname($this->file))) {
            mkdir(dirname($this->file), 0777, true);
        }
        if (!file_exists($this->file)) {
            file_put_contents($this->file, json_encode([]), LOCK_EX);
        }
    }

    public function getAll() {
        if (!file_exists($this->file)) return [];
        $content = file_get_contents($this->file);
        return json_decode($content, true) ?: [];
    }

    public function save($data) {
        return file_put_contents($this->file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    public function add($item) {
        // Atomic read-modify-write would be better with real locking,
        // but LOCK_EX on put helps for simple cases.
        $items = $this->getAll();
        $item['id'] = time() . '_' . uniqid();
        $item['created_at'] = date('Y-m-d H:i:s');
        $items[] = $item;
        return $this->save($items);
    }
}
