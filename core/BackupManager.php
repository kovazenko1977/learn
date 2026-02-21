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
            // Verify zip contains 'data/' directory to avoid accidental extraction of wrong files
            if ($zip->locateName('data/users.json') === false && $zip->locateName('data/settings.json') === false) {
                $zip->close();
                return false;
            }

            if (!is_dir($this->dataDir)) mkdir($this->dataDir, 0755, true);
            if (!is_dir($this->uploadDir)) mkdir($this->uploadDir, 0755, true);

            $zip->extractTo('.');
            $zip->close();
            return true;
        }
        return false;
    }

    public function resetData(string $password): bool {
        if ($password !== '12345') return false;

        // Transactions and Dictionaries to clear
        $filesToClear = ['requests.json', 'notifications.json', 'chat.json', 'services.json', 'templates.json', 'locations.json'];
        foreach ($filesToClear as $f) {
            if (file_exists($this->dataDir . $f)) {
                file_put_contents($this->dataDir . $f, json_encode([]));
            }
        }

        // Wipe and restore default admin
        $defaultAdmin = [[
            'id' => 1,
            'name' => 'Администратор',
            'role' => 'admin',
            'code' => '123456',
            'service_id' => null,
            'telegram_chat_id' => '',
            'info' => 'Главный администратор системы'
        ]];
        file_put_contents($this->dataDir . 'users.json', json_encode($defaultAdmin, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Clear uploads
        foreach (glob($this->uploadDir . "*") as $file) {
            if (is_file($file)) unlink($file);
        }

        return true;
    }
}
