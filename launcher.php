<?php
/**
 * Launcher and Status Checker
 */
header('Content-Type: application/json');

$backend_url = 'http://localhost:8000/';
$status = 'offline';

$ch = curl_init($backend_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    $status = 'online';
}

echo json_encode([
    'app' => 'LabelPro',
    'version' => '1.0.0',
    'backend_status' => $status,
    'php_version' => PHP_VERSION,
    'os' => PHP_OS
]);
