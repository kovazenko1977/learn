<?php
require_once '../includes/AuthManager.php';
AuthManager::check();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

$recipient = $data['email'];
$subject = $data['subject'];
$messageBody = $data['message'];
$attachments = $data['attachments'] ?? [];

// Retrieve SMTP and Mailing settings
require_once '../includes/Storage.php';
$settingsStorage = new Storage('settings.json');
$settings = $settingsStorage->read();

$fromName = $settings['mailing_from_name'] ?? 'Mailing Service';
$fromEmail = $settings['smtp_user'] ?? 'noreply@wes.by';
$replyTo = $settings['mailing_reply_to'] ?? $fromEmail;

$boundary = md5(time());
$headers = [
    'MIME-Version: 1.0',
    'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
    'From: "' . $fromName . '" <' . $fromEmail . '>',
    'Reply-To: ' . $replyTo,
    'X-Mailer: PHP/' . phpversion()
];

// Simple multipart message construction
$message = "--$boundary\r\n";
$message .= "Content-Type: text/html; charset=utf-8\r\n";
$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$message .= $messageBody . "\r\n\r\n";

foreach ($attachments as $att) {
    $filePath = '../uploads/' . $att['filename'];
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $encoded = chunk_split(base64_encode($content));
        $message .= "--$boundary\r\n";
        $message .= "Content-Type: application/octet-stream; name=\"" . $att['original_name'] . "\"\r\n";
        $message .= "Content-Description: " . $att['original_name'] . "\r\n";
        $message .= "Content-Disposition: attachment; filename=\"" . $att['original_name'] . "\"; size=" . strlen($content) . ";\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= $encoded . "\r\n\r\n";
    }
}
$message .= "--$boundary--";

$sent = @mail($recipient, $subject, $message, implode("\r\n", $headers));

// Log history
$historyStorage = new Storage('mailing_history.json');
$history = $historyStorage->read();
$history[] = [
    'timestamp' => date('Y-m-d H:i:s'),
    'email' => $recipient,
    'subject' => $subject,
    'success' => $sent
];
$historyStorage->write($history);

echo json_encode(['success' => $sent]);
