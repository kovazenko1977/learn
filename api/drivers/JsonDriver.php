<?php
// api/drivers/JsonDriver.php
require_once __DIR__ . '/../config.php';

class JsonDriver {
    private string $dataDir;

    public function __construct() {
        $this->dataDir = DATA_DIR;
    }

    private function getFilePath(string $collection): string {
        return $this->dataDir . '/' . preg_replace('/[^a-zA-Z0-9_]/', '', $collection) . '.json';
    }

    public function getAll(string $collection): array {
        $file = $this->getFilePath($collection);
        if (!file_exists($file)) {
            return [];
        }
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    public function getById(string $collection, $id): ?array {
        $items = $this->getAll($collection);
        foreach ($items as $item) {
            if (isset($item['id']) && (string)$item['id'] === (string)$id) {
                return $item;
            }
        }
        return null;
    }

    public function findWhere(string $collection, array $criteria): array {
        $items = $this->getAll($collection);
        return array_values(array_filter($items, function($item) use ($criteria) {
            foreach ($criteria as $k => $v) {
                if (!isset($item[$k]) || (string)$item[$k] !== (string)$v) {
                    return false;
                }
            }
            return true;
        }));
    }

    public function insert(string $collection, array $data): array {
        $items = $this->getAll($collection);

        if (!isset($data['id'])) {
            $maxId = 0;
            foreach ($items as $item) {
                if (isset($item['id']) && is_numeric($item['id']) && (int)$item['id'] > $maxId) {
                    $maxId = (int)$item['id'];
                }
            }
            $data['id'] = $maxId + 1;
        }

        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        $items[] = $data;
        $this->saveAll($collection, $items);
        return $data;
    }

    public function update(string $collection, $id, array $data): ?array {
        $items = $this->getAll($collection);
        $foundIndex = -1;
        foreach ($items as $index => $item) {
            if (isset($item['id']) && (string)$item['id'] === (string)$id) {
                $foundIndex = $index;
                break;
            }
        }

        if ($foundIndex === -1) {
            return null;
        }

        $items[$foundIndex] = array_merge($items[$foundIndex], $data, ['updated_at' => date('Y-m-d H:i:s')]);
        $this->saveAll($collection, $items);
        return $items[$foundIndex];
    }

    public function delete(string $collection, $id): bool {
        $items = $this->getAll($collection);
        $newItems = [];
        $deleted = false;

        foreach ($items as $item) {
            if (isset($item['id']) && (string)$item['id'] === (string)$id) {
                $deleted = true;
            } else {
                $newItems[] = $item;
            }
        }

        if ($deleted) {
            $this->saveAll($collection, $newItems);
        }
        return $deleted;
    }

    public function saveAll(string $collection, array $items): bool {
        $file = $this->getFilePath($collection);
        $json = json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return file_put_contents($file, $json, LOCK_EX) !== false;
    }

    public function testConnection(): bool {
        return is_writable($this->dataDir);
    }
}
