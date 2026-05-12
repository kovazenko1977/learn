<?php
session_start();
require_once __DIR__ . '/../includes/Storage.php';

header('Content-Type: application/json');

function checkAuth() {
    if (!isset($_SESSION['authenticated'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(Storage::getPrices());
    exit;
}

checkAuth();

if ($method === 'POST') {
    $prices = json_decode(file_get_contents('php://input'), true);
    if ($prices === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    if (Storage::savePrices($prices)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save prices']);
    }
    exit;
}
