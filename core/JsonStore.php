<?php
namespace Hop\Core;

class JsonStore {
    private string $filePath;

    public function __construct(string $filePath) {
        $this->filePath = $filePath;
    }

    public function read(): array {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $fp = fopen($this->filePath, "r");
        if (!$fp) return [];

        flock($fp, LOCK_SH);
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    public function save(array $data): bool {
        $fp = fopen($this->filePath, "c+");
        if (!$fp) return false;

        flock($fp, LOCK_EX);
        ftruncate($fp, 0);
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $result = fwrite($fp, $content);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $result !== false;
    }

    public function getNextId(string $idField = 'id'): int {
        $data = $this->read();
        $maxId = 0;
        foreach ($data as $item) {
            if (isset($item[$idField]) && $item[$idField] > $maxId) {
                $maxId = $item[$idField];
            }
        }
        return $maxId + 1;
    }
}
