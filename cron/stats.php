<?php
require_once __DIR__ . '/../includes/Storage.php';

// Simple stats caching
$stats = [
    'patients' => count(Storage::list('patients')),
    'appointments' => count(Storage::list('appointments')),
    'timestamp' => date('c')
];

$cacheFile = __DIR__ . '/../storage/cache/stats_summary.json';
if (!is_dir(dirname($cacheFile))) mkdir(dirname($cacheFile), 0755, true);
file_put_contents($cacheFile, json_encode($stats));

echo "Stats updated\n";
