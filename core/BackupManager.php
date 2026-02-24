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
            // Security: Only extract files that belong to data/ or uploads/
            $allowedDirs = ['data/', 'uploads/'];
            $validZip = false;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                // Basic check if it contains expected structure
                if (strpos($filename, 'data/settings.json') !== false || strpos($filename, 'data/users.json') !== false) {
                    $validZip = true;
                }

                $isAllowed = false;
                foreach ($allowedDirs as $dir) {
                    if (strpos($filename, $dir) === 0) {
                        $isAllowed = true;
                        break;
                    }
                }

                // If file is not in allowed directories, skip it or handle error
                if (!$isAllowed) continue;

                // Security: Prevent Directory Traversal in zip filenames
                if (strpos($filename, '..') !== false) continue;

                $zip->extractTo('.', $filename);
            }

            $zip->close();
            return $validZip;
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
            'username' => 'admin',
            'password' => password_hash('admin', PASSWORD_DEFAULT),
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
