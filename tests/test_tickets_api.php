<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

echo "Testing Tickets, Form Fields & Comments...\n";

$baseUrl = 'http://127.0.0.1:8088';

// Login as admin
$ch = curl_init($baseUrl . '/api/auth.php?action=login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'admin', 'password' => 'admin123']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$authOut = curl_exec($ch);
curl_close($ch);

$authData = json_decode($authOut, true);
if (!($authData['success'] ?? false)) {
    echo "Auth test FAILED: " . ($authData['error'] ?? 'Unknown error') . "\n";
    exit(1);
}
$token = $authData['token'];
echo "Auth Token obtained successfully.\n";

// 1. Test Form Fields
$ch = curl_init($baseUrl . '/api/form-fields.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$fieldsOut = curl_exec($ch);
curl_close($ch);

$fieldsData = json_decode($fieldsOut, true);
if (!($fieldsData['success'] ?? false) || count($fieldsData['fields']) < 1) {
    echo "Form Fields test FAILED!\n";
    exit(1);
}
echo "Form Fields test PASSED: Found " . count($fieldsData['fields']) . " fields.\n";

// 2. Test Tickets List
$ch = curl_init($baseUrl . '/api/tickets.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$ticketsOut = curl_exec($ch);
curl_close($ch);

$ticketsData = json_decode($ticketsOut, true);
if (!($ticketsData['success'] ?? false) || count($ticketsData['tickets']) < 1) {
    echo "Tickets List test FAILED!\n";
    exit(1);
}
echo "Tickets List test PASSED: Loaded " . count($ticketsData['tickets']) . " tickets.\n";

echo "ALL ENDPOINT TESTS PASSED SUCCESSFUL!\n";
