<?php
namespace Hop\Core;

use ZipArchive;

class BackupManager {
    private string $dataDir;

    public function __construct(string $dataDir) {
        $this->dataDir = rtrim($dataDir, '/') . '/';
    }

    public function createBackup(): ?string {
        $zipFile = sys_get_temp_dir() . '/hop_backup_' . date('Ymd_His') . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        $files = glob($this->dataDir . '*.json');
        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();
        return $zipFile;
    }

    public function restoreBackup(string $zipPath): bool {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            // Only extract .json files to prevent security issues
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (pathinfo($filename, PATHINFO_EXTENSION) === 'json') {
                    $zip->extractTo($this->dataDir, $filename);
                }
            }
            $zip->close();
            return true;
        }
        return false;
    }
}
