<?php

declare(strict_types=1);

namespace App\Storage;

class JsonDriver implements StorageInterface
{
    private string $storagePath;

    public function __construct(string $storagePath)
    {
        $this->storagePath = rtrim($storagePath, '/') . '/';
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    private function getFilePath(string $collection): string
    {
        return $this->storagePath . $collection . '.json';
    }

    private function readCollection(string $collection): array
    {
        $file = $this->getFilePath($collection);
        if (!file_exists($file)) {
            return [];
        }

        $fp = fopen($file, 'r');
        if (!$fp) return [];

        flock($fp, LOCK_SH);
        $content = '';
        while (!feof($fp)) {
            $content .= fread($fp, 8192);
        }
        flock($fp, LOCK_UN);
        fclose($fp);

        return json_decode($content, true) ?: [];
    }

    private function writeCollection(string $collection, array $data): void
    {
        $file = $this->getFilePath($collection);
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $fp = fopen($file, 'w');
        if ($fp) {
            flock($fp, LOCK_EX);
            fwrite($fp, $content);
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    public function find(string $collection, array $criteria = []): array
    {
        $items = $this->readCollection($collection);
        if (empty($criteria)) {
            return $items;
        }

        return array_values(array_filter($items, function ($item) use ($criteria) {
            foreach ($criteria as $key => $value) {
                if (!isset($item[$key]) || $item[$key] != $value) {
                    return false;
                }
            }
            return true;
        }));
    }

    public function findOne(string $collection, array $criteria = []): ?array
    {
        $results = $this->find($collection, $criteria);
        return $results[0] ?? null;
    }

    public function insert(string $collection, array $data): string
    {
        $items = $this->readCollection($collection);
        $id = $data['id'] ?? uniqid();
        $data['id'] = $id;
        $data['created_at'] = date('Y-m-d H:i:s');
        $items[] = $data;
        $this->writeCollection($collection, $items);
        return (string)$id;
    }

    public function update(string $collection, string $id, array $data): bool
    {
        $items = $this->readCollection($collection);
        $updated = false;
        foreach ($items as &$item) {
            if ($item['id'] == $id) {
                $item = array_merge($item, $data, ['id' => $id, 'updated_at' => date('Y-m-d H:i:s')]);
                $updated = true;
                break;
            }
        }
        if ($updated) {
            $this->writeCollection($collection, $items);
        }
        return $updated;
    }

    public function delete(string $collection, string $id): bool
    {
        $items = $this->readCollection($collection);
        $initialCount = count($items);
        $items = array_filter($items, fn($item) => $item['id'] != $id);
        if (count($items) < $initialCount) {
            $this->writeCollection($collection, array_values($items));
            return true;
        }
        return false;
    }
}
