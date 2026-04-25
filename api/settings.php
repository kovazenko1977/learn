<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';

$storage = Storage::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $settings = $storage->getSettings();
    unset($settings['jwt_secret']);
    echo json_encode($settings);
} elseif ($method === 'POST') {
    $user = Auth::authenticate();
    Auth::checkRole($user, ['Administrator']);
    $data = json_decode(file_get_contents('php://input'), true);

    $storage->write('settings', $data);
    echo json_encode(['success' => true]);
}
