<?php
/**
 * PHP Proxy for FastAPI Backend
 */
$backend_url = 'http://localhost:8000';
$request_uri = $_SERVER['REQUEST_URI'];
$api_path = str_replace('/api.php', '', $request_uri);

$ch = curl_init($backend_url . $api_path);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'PUT') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
}

$headers = [];
foreach (getallheaders() as $name => $value) {
    if ($name != 'Host') {
        $headers[] = "$name: $value";
    }
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

header("Content-Type: $content_type", true, $http_code);
echo $response;
curl_close($ch);
