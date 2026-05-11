<?php
require_once __DIR__ . '/../includes/Storage.php';
use App\Storage;

header('Content-Type: application/json');

$files = Storage::getAll();
// Sort by date descending
usort($files, function($a, $b) {
    return strcmp($b['uploaded_at'], $a['uploaded_at']);
});

echo json_encode($files);
