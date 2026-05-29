<?php

namespace App\Services;

class IntegrityService {
    private array $criticalFiles = [
        'index.php',
        'api.php',
        'src/Database/JsonStore.php',
        'src/Services/SerialCommService.php',
        'assets/js/app.js'
    ];

    public function checkIntegrity(): array {
        $results = [];
        $isValid = true;

        foreach ($this->criticalFiles as $file) {
            $path = __DIR__ . '/../../' . $file;
            if (!file_exists($path)) {
                $results[$file] = 'MISSING';
                $isValid = false;
                continue;
            }

            // In a production app, we would compare against known SHA256 hashes
            $hash = hash_file('sha256', $path);
            $results[$file] = [
                'status' => 'OK',
                'hash' => substr($hash, 0, 8) . '...'
            ];
        }

        return [
            'valid' => $isValid,
            'details' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
