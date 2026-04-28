<?php
function callApi($url, $method = 'GET', $data = null, $token = null, $files = null) {
    $ch = curl_init("http://localhost:8000/" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = [];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }

    if ($files) {
        curl_setopt($ch, CURLOPT_POST, true);
        $data['file'] = $files['file'];
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    } elseif ($data) {
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $code, 'data' => json_decode($response, true), 'raw' => $response];
}

// 1. Login
echo "Testing Login...\n";
$login = callApi('api/login.php', 'POST', ['username' => 'admin', 'password' => 'admin123']);
if ($login['code'] !== 200 || empty($login['data']['token'])) {
    die("Login failed: " . $login['raw'] . "\n");
}
$token = $login['data']['token'];
echo "Login successful.\n";

// 2. Create Task
echo "Testing Create Task...\n";
$newTask = callApi('api/tasks.php?action=create', 'POST', [
    'title' => 'Test Task',
    'description' => 'Verify API works',
    'priority' => 'High'
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
    'comment' => 'This is a test comment'
], $token);
if ($comment['code'] !== 200) {
    die("Add comment failed: " . $comment['raw'] . "\n");
}
echo "Comment added.\n";

// 4. Verify Task and History
echo "Verifying Task Data...\n";
$tasks = callApi('api/tasks.php', 'GET', null, $token);
$foundTask = null;
foreach ($tasks['data'] as $t) {
    if ($t['id'] === $taskId) {
        $foundTask = $t;
        break;
    }
}

if (!$foundTask || count($foundTask['comments']) === 0 || count($foundTask['history']) === 0) {
    die("Task verification failed.\n");
}
echo "Task verified successfully.\n";
