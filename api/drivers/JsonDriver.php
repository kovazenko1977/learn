<?php
declare(strict_types=1);

require_once __DIR__ . '/../storage.php';

class JsonDriver implements StorageInterface {
    private string $dataDir;

    public function __construct(string $dataDir) {
        $this->dataDir = rtrim($dataDir, '/\\');
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }

    private function getFilePath(string $collection): string {
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $collection);
        return $this->dataDir . '/' . $safeName . '.json';
    }

    private function readCollection(string $collection): array {
        $file = $this->getFilePath($collection);
        if (!file_exists($file)) {
            return [];
        }

        $fp = fopen($file, 'rb');
        if (!$fp) {
            return [];
        }

        flock($fp, LOCK_SH);
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        if ($content === false || trim($content) === '') {
            return [];
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    public function getAll(string $collection): array {
        return $this->readCollection($collection);
    }

    public function findById(string $collection, $id): ?array {
        $items = $this->readCollection($collection);
        foreach ($items as $item) {
            if (isset($item['id']) && (string)$item['id'] === (string)$id) {
                return $item;
            }
        }
        return null;
    }

    public function findBy(string $collection, array $criteria): array {
        $items = $this->readCollection($collection);
        $result = [];
        foreach ($items as $item) {
            $match = true;
            foreach ($criteria as $k => $v) {
                if (!isset($item[$k]) || (string)$item[$k] !== (string)$v) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $result[] = $item;
            }
        }
        return $result;
    }

    public function findOneBy(string $collection, array $criteria): ?array {
        $matches = $this->findBy($collection, $criteria);
        return !empty($matches) ? $matches[0] : null;
    }

    public function insert(string $collection, array $data): array {
        $file = $this->getFilePath($collection);
        $fp = fopen($file, 'c+b');
        if (!$fp) {
            throw new RuntimeException("Could not open collection file: $file");
        }

        flock($fp, LOCK_EX);
        $content = stream_get_contents($fp);
        $items = ($content !== false && trim($content) !== '') ? (json_decode($content, true) ?: []) : [];

        if (!isset($data['id'])) {
            $maxId = 0;
            foreach ($items as $item) {
                if (isset($item['id']) && is_numeric($item['id'])) {
                    $maxId = max($maxId, (int)$item['id']);
                }
            }
            $data['id'] = $maxId + 1;
        }

        $items[] = $data;

        $json = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $json);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $data;
    }

    public function update(string $collection, $id, array $data): ?array {
        $file = $this->getFilePath($collection);
        if (!file_exists($file)) {
            return null;
        }

        $fp = fopen($file, 'c+b');
        if (!$fp) {
            return null;
        }

        flock($fp, LOCK_EX);
        $content = stream_get_contents($fp);
        $items = ($content !== false && trim($content) !== '') ? (json_decode($content, true) ?: []) : [];

        $found = false;
        $updatedItem = null;

        foreach ($items as $key => $item) {
            if (isset($item['id']) && (string)$item['id'] === (string)$id) {
                $data['id'] = $item['id'];
                $updatedItem = array_merge($item, $data);
                $items[$key] = $updatedItem;
                $found = true;
                break;
            }
        }

        if ($found) {
            $json = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $json);
            fflush($fp);
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        return $updatedItem;
    }

    public function delete(string $collection, $id): bool {
        $file = $this->getFilePath($collection);
        if (!file_exists($file)) {
            return false;
        }

        $fp = fopen($file, 'c+b');
        if (!$fp) {
            return false;
        }

        flock($fp, LOCK_EX);
        $content = stream_get_contents($fp);
        $items = ($content !== false && trim($content) !== '') ? (json_decode($content, true) ?: []) : [];

        $found = false;
        $newItems = [];
        foreach ($items as $item) {
            if (isset($item['id']) && (string)$item['id'] === (string)$id) {
                $found = true;
                continue;
            }
            $newItems[] = $item;
        }

        if ($found) {
            $json = json_encode(array_values($newItems), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $json);
            fflush($fp);
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        return $found;
    }

    public function testConnection(): bool {
        return is_dir($this->dataDir) && is_writable($this->dataDir);
    }

    public function getDriverName(): string {
        return 'json';
    }
}
