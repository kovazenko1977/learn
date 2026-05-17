<?php
require_once __DIR__ . '/../src/JsonStore.php';
header('Content-Type: application/json');

$code = $_GET['code'] ?? null;
if (!$code) {
    echo json_encode(['error' => 'No code provided']);
    exit;
}

$store = new \App\JsonStore(__DIR__ . '/../data/popups.json');
$popup = $store->getByCode($code);

if ($popup) {
    echo json_encode($popup);
} else {
    echo json_encode(['error' => 'Popup not found']);
}
