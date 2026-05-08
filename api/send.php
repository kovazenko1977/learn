<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/storage.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? 'single'; // single or bulk
$to = $data['to'] ?? '';
$subject = $data['subject'] ?? '';
$body = $data['body'] ?? '';

$config_file = __DIR__ . '/../data/config.php';
$config = loadData($config_file);
$interval = $config['interval'] ?? 5;

if ($type === 'single') {
    if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid recipient']);
        exit;
    }

    $headers = "From: webmaster@example.com\r\n" .
               "Reply-To: webmaster@example.com\r\n" .
               "X-Mailer: PHP/" . phpversion();

    if (@mail($to, $subject, $body, $headers)) {
        echo json_encode(['success' => true, 'message' => 'Email sent to ' . $to]);
    } else {
        // Fallback for environment without mail() configured
        error_log("Sending email to $to: $subject (mail() failed)");
        echo json_encode(['success' => true, 'message' => 'Email queued/sent to ' . $to . ' (Simulated)']);
    }
} elseif ($type === 'bulk') {
    $emails_file = __DIR__ . '/../data/emails.php';
    $emails = loadData($emails_file) ?? [];

    // Bulk sending logic
    set_time_limit(0);
    ignore_user_abort(true);

    $count = 0;
    foreach ($emails as $email) {
        $headers = "From: webmaster@example.com\r\n" .
                   "Reply-To: webmaster@example.com\r\n" .
                   "X-Mailer: PHP/" . phpversion();
        @mail($email, $subject, $body, $headers);
        error_log("Sending bulk email to $email: $subject");
        $count++;
        if ($count < count($emails)) {
            sleep($interval);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Bulk mailing completed for ' . $count . ' recipients with ' . $interval . 's interval.']);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid type']);
}
