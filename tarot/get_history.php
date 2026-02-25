<?php
header('Content-Type: application/json');
$filePath = __DIR__ . '/data/history.json';
if (file_exists($filePath)) {
    echo file_get_contents($filePath);
} else {
    echo json_encode([]);
}
