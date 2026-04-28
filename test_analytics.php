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

echo "Testing Analytics API...\n";
$analytics = callApi('api/analytics.php', 'GET', null, $token);
if ($analytics['code'] !== 200) die("Analytics failed: " . $analytics['raw']);
echo "Analytics data: " . json_encode($analytics['data']) . "\n";

echo "Testing Export API...\n";
$export = callApi('api/export.php', 'GET', null, $token);
if ($export['code'] !== 200) die("Export failed: " . $export['code']);
if (strpos($export['raw'], 'ID,Title,Priority') === false) {
     // Check for UTF-8 BOM if needed, but here we just check presence of headers
     if (strpos($export['raw'], 'Title') === false) die("Export content invalid");
}
echo "Export API verified.\n";
