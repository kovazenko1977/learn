<?php
namespace App;

class Storage {
    private static $storageFile = __DIR__ . '/../data/files.json';

    public static function getAll() {
        if (!file_exists(self::$storageFile)) {
            return [];
        }
        $data = @file_get_contents(self::$storageFile);
        return json_decode($data, true) ?: [];
    }

    public static function save($fileData) {
        $files = self::getAll();
        $newFile = array_merge($fileData, [
            'id' => uniqid(),
            'uploaded_at' => date('Y-m-d H:i:s')
        ]);
        $files[] = $newFile;
        file_put_contents(self::$storageFile, json_encode($files, JSON_PRETTY_PRINT), LOCK_EX);
        return $newFile;
    }

    public static function delete($id) {
        $files = self::getAll();
        $fileToDelete = null;
        $remainingFiles = [];

        foreach ($files as $file) {
            if ($file['id'] === $id) {
                $fileToDelete = $file;
            } else {
                $remainingFiles[] = $file;
            }
        }

        if ($fileToDelete) {
            $path = __DIR__ . '/../uploads/' . $fileToDelete['filename'];
            if (file_exists($path)) {
                @unlink($path);
            }
            file_put_contents(self::$storageFile, json_encode(array_values($remainingFiles), JSON_PRETTY_PRINT), LOCK_EX);
            return true;
        }
        return false;
    }

    public static function getById($id) {
        $files = self::getAll();
        foreach ($files as $file) {
            if ($file['id'] === $id) {
                return $file;
            }
        }
        return null;
    }
}
