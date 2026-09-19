<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

echo "Testing Tickets, Form Fields & Comments...\n";

// Login as admin
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['action'] = 'login';
$_POST = ['username' => 'admin', 'password' => 'admin123'];

ob_start();
require __DIR__ . '/../api/auth.php';
$authOut = ob_get_clean();
$authData = json_decode($authOut, true);
$token = $authData['token'];

echo "Auth Token obtained.\n";

// Set token in environment header
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

// 1. Test Form Fields
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [];
ob_start();
require __DIR__ . '/../api/form-fields.php';
$fieldsOut = ob_get_clean();
$fieldsData = json_decode($fieldsOut, true);

if (!($fieldsData['success'] ?? false) || count($fieldsData['fields']) < 1) {
    echo "Form Fields test FAILED!\n";
    exit(1);
}
echo "Form Fields test PASSED: Found " . count($fieldsData['fields']) . " fields.\n";

// 2. Test Tickets List
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [];
ob_start();
require __DIR__ . '/../api/tickets.php';
$ticketsOut = ob_get_clean();
$ticketsData = json_decode($ticketsOut, true);

if (!($ticketsData['success'] ?? false) || count($ticketsData['tickets']) < 1) {
    echo "Tickets List test FAILED!\n";
    exit(1);
}
echo "Tickets List test PASSED: Loaded " . count($ticketsData['tickets']) . " tickets.\n";

echo "ALL ENDPOINT TESTS PASSED SUCCESSFUL!\n";
