<?php

namespace App\Database;

class JsonStore
{
    private string $filepath;

    public function __construct(string $filepath)
    {
        $this->filepath = $filepath;
        if (!file_exists($this->filepath)) {
            $this->save([]);
        }
    }

    public function getAll(): array
    {
        $content = file_get_contents($this->filepath);
        return json_decode($content, true) ?: [];
    }

    public function set(array $data): void
    {
        $this->save($data);
    }

    private function save(array $data): void
    {
        $fp = fopen($this->filepath, 'c+');
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    public function findById(string $id): ?array
    {
        $items = $this->getAll();
        foreach ($items as $item) {
            if (isset($item['id']) && $item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }

    public function saveItem(array $item): void
    {
        $items = $this->getAll();
        $found = false;
        foreach ($items as &$existingItem) {
            if (isset($existingItem['id']) && $existingItem['id'] === $item['id']) {
                $existingItem = $item;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $items[] = $item;
        }
        $this->save($items);
    }

    public function deleteById(string $id): void
    {
        $items = $this->getAll();
        $items = array_filter($items, fn($item) => $item['id'] !== $id);
        $this->save(array_values($items));
    }
}
