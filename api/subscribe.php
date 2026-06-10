<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Database\JsonStore;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$sectionId = $_POST['section_id'] ?? '';

if (!$email || !$sectionId) {
    echo json_encode(['success' => false, 'error' => 'Invalid email or section']);
    exit;
}

$store = new JsonStore(__DIR__ . '/../data/subscribers.json');
$subs = $store->getAll();

foreach ($subs as $sub) {
    if ($sub['email'] === $email && $sub['section_id'] === $sectionId) {
        echo json_encode(['success' => true, 'message' => 'Already subscribed']);
        exit;
    }
}

$subs[] = [
    'email' => $email,
    'section_id' => $sectionId,
    'date' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR']
];

$store->set($subs);

echo json_encode(['success' => true, 'message' => 'Subscribed successfully']);
