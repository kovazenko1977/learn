<?php

namespace App\Helpers;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class BackupManager {
    private string $dataDir;

    public function __construct(string $dataDir) {
        $this->dataDir = rtrim($dataDir, DIRECTORY_SEPARATOR);
    }

    /**
     * Creates a ZIP backup of all JSON files in the data directory.
     * Returns the path to the temporary ZIP file.
     */
    public function createBackup(): ?string {
        $tempFile = tempnam(sys_get_temp_dir(), 'san_backup_') . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        $files = glob($this->dataDir . DIRECTORY_SEPARATOR . '*.json');
        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();
        return $tempFile;
    }

    /**
     * Restores data from a ZIP backup.
     */
    public function restoreBackup(string $zipPath): array {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'message' => 'Не удалось открыть ZIP-архив.'];
        }

        // Validate content: only .json files allowed
        $jsonFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (pathinfo($filename, PATHINFO_EXTENSION) !== 'json') {
                $zip->close();
                return ['success' => false, 'message' => 'В архиве содержатся недопустимые файлы. Разрешены только .json.'];
            }
            $jsonFiles[] = $filename;
        }

        if (empty($jsonFiles)) {
            $zip->close();
            return ['success' => false, 'message' => 'Архив пуст или не содержит JSON файлов.'];
        }

        // Extract files
        if (!$zip->extractTo($this->dataDir)) {
            $zip->close();
            return ['success' => false, 'message' => 'Ошибка при извлечении файлов. Проверьте права доступа.'];
        }

        $zip->close();
        return ['success' => true, 'count' => count($jsonFiles)];
    }
}
