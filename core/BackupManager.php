<?php
namespace Hop\Core;

class BackupManager {
    private string $dataDir;
    private string $uploadDir;

    public function __construct(string $dataDir, string $uploadDir) {
        $this->dataDir = rtrim($dataDir, '/') . '/';
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
    }

    public function createBackup(): string {
        $zipFile = 'backup_' . date('Y-m-d_H-i-s') . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE) !== TRUE) {
            throw new \Exception("Cannot create zip file");
        }

        // Add JSON files
        foreach (glob($this->dataDir . "*.json") as $file) {
            $zip->addFile($file, 'data/' . basename($file));
        }

        // Add uploads
        foreach (glob($this->uploadDir . "*") as $file) {
            if (is_file($file)) {
                $zip->addFile($file, 'uploads/' . basename($file));
            }
        }

        $zip->close();
        return $zipFile;
    }

    public function restoreBackup(string $zipFilePath): bool {
        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath) === TRUE) {
            $zip->extractTo('.');
            $zip->close();
            return true;
        }
        return false;
    }

    public function resetData(string $password): bool {
        if ($password !== '12345') return false;

        // Transactions to clear
        $filesToClear = ['requests.json', 'notifications.json', 'chat.json'];
        foreach ($filesToClear as $f) {
            if (file_exists($this->dataDir . $f)) {
                file_put_contents($this->dataDir . $f, json_encode([]));
            }
        }

        // Clear uploads
        foreach (glob($this->uploadDir . "*") as $file) {
            if (is_file($file)) unlink($file);
        }

        return true;
    }
}
