<?php

namespace App\Database;

class JsonStore {
    private string $filePath;

    public function __construct(string $filePath) {
        $this->filePath = $filePath;
        if (!file_exists(dirname($this->filePath))) {
            mkdir(dirname($this->filePath), 0755, true);
        }
    }

    public function getData(): array {
        if (!file_exists($this->filePath)) {
            return [];
        }
        $content = file_get_contents($this->filePath);
        return json_decode($content, true) ?: [];
    }

    public function setData(array $data): bool {
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($this->filePath, $content) !== false;
    }
}
