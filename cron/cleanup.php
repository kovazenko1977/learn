<?php
/**
 * Cron cleanup script
 * Deletes orphaned .tmp files and truncates large logs
 */

$storageDir = __DIR__ . '/../storage/data/';
$logDir = __DIR__ . '/../storage/logs/';

// 1. Cleanup .tmp files older than 1 hour
$it = new RecursiveDirectoryIterator($storageDir);
foreach (new RecursiveIteratorIterator($it) as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'tmp' && (time() - filemtime($file) > 3600)) {
        unlink($file);
    }
}

// 2. Truncate logs if > 5MB
if (is_dir($logDir)) {
    foreach (glob($logDir . '/*.log') as $logFile) {
        if (filesize($logFile) > 5 * 1024 * 1024) {
            $handle = fopen($logFile, 'r+');
            ftruncate($handle, 0);
            fclose($handle);
            file_put_contents($logFile, "[" . date('c') . "] Log truncated due to size limit.\n");
        }
    }
}

echo "Cleanup completed at " . date('c') . "\n";
