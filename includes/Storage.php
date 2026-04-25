<?php

class Storage {
    private static $baseDir = __DIR__ . '/../data/';

    public static function read(string $filename) {
        $path = self::$baseDir . $filename;
        if (!file_exists($path)) {
            return [];
        }
        $content = file_get_contents($path);
        return json_decode($content, true) ?: [];
    }

    public static function write(string $filename, array $data): bool {
        $path = self::$baseDir . $filename;
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $tempPath = $path . '.' . uniqid() . '.tmp';
        if (file_put_contents($tempPath, $json, LOCK_EX) !== false) {
            if (rename($tempPath, $path)) {
                return true;
            }
            unlink($tempPath);
        }
        return false;
    }

    public static function getAll(string $filename): array {
        return self::read($filename);
    }

    public static function getById(string $filename, $id, string $idField = 'id') {
        $items = self::read($filename);
        foreach ($items as $item) {
            if ($item[$idField] == $id) {
                return $item;
            }
        }
        return null;
    }

    public static function saveItem(string $filename, array $item, string $idField = 'id'): bool {
        $items = self::read($filename);
        $found = false;
        foreach ($items as &$existing) {
            if ($existing[$idField] == $item[$idField]) {
                $existing = array_merge($existing, $item);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $items[] = $item;
        }
        return self::write($filename, $items);
    }

    public static function deleteItem(string $filename, $id, string $idField = 'id'): bool {
        $items = self::read($filename);
        $initialCount = count($items);
        $items = array_filter($items, function($item) use ($id, $idField) {
            return $item[$idField] != $id;
        });
        if (count($items) === $initialCount) {
            return false;
        }
        return self::write($filename, array_values($items));
    }
}
