<?php
require_once __DIR__ . '/../includes/Storage.php';

// Simple backup logic
$dataDir = __DIR__ . '/../storage/data/';
$backupDir = __DIR__ . '/../storage/backups/';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$zip = new ZipArchive();
$filename = $backupDir . "backup_" . date('Y-m-d_H-i-s') . ".zip";

if ($zip->open($filename, ZipArchive::CREATE) !== TRUE) {
    exit("cannot open <$filename>\n");
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dataDir),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $name => $file) {
    if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($dataDir));
        $zip->addFile($filePath, $relativePath);
    }
}

$zip->close();
echo "Backup created: $filename\n";
