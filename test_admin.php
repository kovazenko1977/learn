<?php
function callApi($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init("http://localhost:8000/" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    $headers = [];
    if ($token) $headers[] = "Authorization: Bearer $token";
    if ($data) {
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($response, true), 'raw' => $response];
}

$login = callApi('api/login.php', 'POST', ['username' => 'admin', 'password' => 'admin123']);
$token = $login['data']['token'];

echo "Testing Admin Save User...\n";
$saveUser = callApi('api/admin.php?action=save_user', 'POST', [
    'username' => 'tech1',
    'name' => 'Technician One',
    'role' => 'Executor',
    'department' => 'IT Support'
], $token);
if ($saveUser['code'] !== 200) die("Save user failed: " . $saveUser['raw']);

echo "Testing Admin Get Users...\n";
$getUsers = callApi('api/admin.php?action=users', 'GET', null, $token);
$found = false;
foreach ($getUsers['data'] as $u) {
    if ($u['username'] === 'tech1') { $found = true; break; }
}
if (!$found) die("User tech1 not found in list");

echo "Admin API verified.\n";
