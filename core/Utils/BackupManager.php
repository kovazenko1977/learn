<?php

declare(strict_types=1);

namespace App\Utils;

class BackupManager
{
    private string $backupDir;
    private array $sourceDirs;

    public function __construct(string $backupDir, array $sourceDirs)
    {
        $this->backupDir = rtrim($backupDir, '/') . '/';
        $this->sourceDirs = $sourceDirs;

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
    }

    public function createBackup(): string
    {
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = $this->backupDir . $filename;

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
            throw new \Exception("Cannot create zip file");
        }

        foreach ($this->sourceDirs as $dir) {
            $this->addDirToZip($zip, $dir, basename($dir));
        }

        $zip->close();
        return $zipPath;
    }

    private function addDirToZip(\ZipArchive $zip, string $path, string $zipPath): void
    {
        if (!is_dir($path)) return;

        $files = scandir($path);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $path . '/' . $file;
            $localPath = $zipPath . '/' . $file;

            if (is_dir($filePath)) {
                $this->addDirToZip($zip, $filePath, $localPath);
            } else {
                $zip->addFile($filePath, $localPath);
            }
        }
    }
}
