<?php
function callApi($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init("http://localhost:8000/" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = [];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }

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

// 0. Seed
echo "Seeding...\n";
callApi('api/seed.php?secret=seed123', 'GET');

// 1. Login
echo "Testing Login...\n";
$login = callApi('api/login.php?action=login', 'POST', ['username' => 'admin', 'password' => 'admin123']);
if ($login['code'] !== 200 || empty($login['data']['token'])) {
    die("Login failed with code " . $login['code'] . ": " . $login['raw'] . "\n");
}
$token = $login['data']['token'];
echo "Login successful.\n";

// 2. Create Task
echo "Testing Create Task...\n";
$newTask = callApi('api/tasks.php?action=create', 'POST', [
    'title' => 'Test Task from Jules Secured',
    'priority' => 'High',
    'category' => 'IT Support'
], $token);
if ($newTask['code'] !== 200) {
    die("Create task failed: " . $newTask['raw'] . "\n");
}
$taskId = $newTask['data']['id'];
echo "Task created: $taskId\n";

// 3. Add Comment
echo "Testing Add Comment...\n";
$comment = callApi('api/tasks.php?action=add_comment', 'POST', [
    'id' => $taskId,
    'comment' => 'Automated test comment secured'
], $token);
if ($comment['code'] !== 200) {
    die("Add comment failed: " . $comment['raw'] . "\n");
}
echo "Comment added.\n";

// 4. Verify Task
echo "Verifying Task...\n";
$tasks = callApi('api/tasks.php', 'GET', null, $token);
$found = false;
foreach ($tasks['data'] as $t) {
    if ($t['id'] === $taskId) {
        $found = true;
        break;
    }
}
if (!$found) die("Task not found in list.\n");
echo "Task verified.\n";

// 5. Analytics
echo "Testing Analytics...\n";
$stats = callApi('api/analytics.php', 'GET', null, $token);
if ($stats['code'] !== 200) die("Analytics failed.\n");
echo "Analytics successful: Total Tasks = " . $stats['data']['total'] . "\n";
