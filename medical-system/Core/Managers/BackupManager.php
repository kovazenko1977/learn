<?php
namespace Medical\Core\Managers;

class BackupManager {
    private $dataDir;

    public function __construct() {
        $this->dataDir = __DIR__ . '/../../data/';
    }

    public function createBackup() {
        $backupFile = __DIR__ . '/../../uploads/backup_' . date('Y-m-d_H-i-s') . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($backupFile, \ZipArchive::CREATE) !== TRUE) {
            return false;
        }

        $files = glob($this->dataDir . '*.json');
        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();
        return $backupFile;
    }

    public function restoreBackup($zipPath) {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === TRUE) {
            // Clear current data files first
            $files = glob($this->dataDir . '*.json');
            foreach ($files as $file) {
                unlink($file);
            }

            $zip->extractTo($this->dataDir);
            $zip->close();
            return true;
        }
        return false;
    }

    public function resetSystem($password) {
        if ($password !== '12345') {
            return false;
        }

        $files = glob($this->dataDir . '*.json');
        foreach ($files as $file) {
            $base = basename($file, '.json');
            if ($base === 'staff') {
                $defaultAdmin = [[
                    "id" => "admin_1",
                    "name" => "Администратор",
                    "role" => "admin",
                    "specialization" => "Система",
                    "access_code" => "123456"
                ]];
                file_put_contents($file, json_encode($defaultAdmin, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                file_put_contents($file, json_encode([]));
            }
        }

        return true;
    }
}
