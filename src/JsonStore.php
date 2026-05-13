<?php

namespace App;

class JsonStore {
    private string $filePath;

    public function __construct(string $filePath) {
        $this->filePath = $filePath;
        if (!file_exists($this->filePath)) {
            if (!file_exists(dirname($this->filePath))) {
                mkdir(dirname($this->filePath), 0777, true);
            }
            file_put_contents($this->filePath, json_encode([]));
        }
    }

    public function getAll(): array {
        $handle = fopen($this->filePath, 'r');
        if (!$handle) return [];

        flock($handle, LOCK_SH);
        $content = "";
        if (filesize($this->filePath) > 0) {
            $content = fread($handle, filesize($this->filePath));
        }
        flock($handle, LOCK_UN);
        fclose($handle);

        return json_decode($content, true) ?: [];
    }

    public function saveAll(array $data): bool {
        $handle = fopen($this->filePath, 'c');
        if (!$handle) return false;

        if (flock($handle, LOCK_EX)) {
            ftruncate($handle, 0);
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            fwrite($handle, $json);
            fflush($handle);
            flock($handle, LOCK_UN);
            fclose($handle);
            return true;
        }

        fclose($handle);
        return false;
    }

    public function getById(string $id): ?array {
        $data = $this->getAll();
        return $data[$id] ?? null;
    }

    public function getByCode(string $code): ?array {
        $data = $this->getAll();
        foreach ($data as $item) {
            if (isset($item['code']) && $item['code'] === $code) {
                return $item;
            }
        }
        return null;
    }

    public function set(string $id, array $item): void {
        $data = $this->getAll();
        $data[$id] = $item;
        $this->saveAll($data);
    }

    public function delete(string $id): void {
        $data = $this->getAll();
        if (isset($data[$id])) {
            unset($data[$id]);
            $this->saveAll($data);
        }
    }
}
