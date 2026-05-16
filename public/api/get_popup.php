<?php
require_once __DIR__ . '/../../src/autoload.php';
use App\Database\JsonStore;

$code = $_GET['code'] ?? '';
if (!$code) {
    http_response_code(400);
    exit;
}

$store = new JsonStore(__DIR__ . '/../../data');
$popup = $store->findByCode('popups', $code);

if (!$popup) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/json');
echo json_encode([
    'title' => $popup['title'],
    'content' => $popup['content'],
    'image' => $popup['image'] ?? null,
    'animation' => $popup['animation'] ?? 'fade'
]);
