<?php

class Storage {
    private static $basePath = __DIR__ . '/../storage/data/';

    public static function getPath($entity, $id = null) {
        if ($id) {
            return self::$basePath . $entity . '/' . $id . '.json';
        }
        return self::$basePath . $entity . '.json';
    }

    public static function list($entity) {
        $dir = self::$basePath . $entity;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            return [];
        }
        $files = glob($dir . '/*.json');
        $items = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && $data !== null) {
                $items[] = $data;
            } else {
                Logger::log("Corrupted JSON in $file", "error", "system.log");
            }
        }
        return $items;
    }

    public static function read($entity, $id) {
        $file = self::getPath($entity, $id);
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }
        return null;
    }

    public static function write($entity, $id, $data) {
        $file = self::getPath($entity, $id);
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tmpFile = $file . '.' . uniqid() . '.tmp';
        file_put_contents($tmpFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Atomic rename
        if (rename($tmpFile, $file)) {
            return true;
        }
        if (file_exists($tmpFile)) unlink($tmpFile);
        return false;
    }

    public static function delete($entity, $id) {
        $file = self::getPath($entity, $id);
        if (file_exists($file)) {
            return unlink($file);
        }
        return false;
    }

    public static function saveVersion($entity, $id, $data) {
        $versionPath = __DIR__ . '/../storage/versions/' . $entity . '/' . $id . '/';
        if (!is_dir($versionPath)) {
            mkdir($versionPath, 0755, true);
        }
        $vNum = count(glob($versionPath . 'v*.json')) + 1;
        $file = $versionPath . 'v' . $vNum . '.json';
        $entry = [
            'version' => $vNum,
            'timestamp' => date('c'),
            'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'system',
            'data' => $data
        ];
        file_put_contents($file, json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
