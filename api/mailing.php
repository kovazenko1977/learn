<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';

Auth::requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(Storage::read('messages'));
} elseif ($method === 'POST') {
    $data = Security::sanitize(json_decode(file_get_contents('php://input'), true));

    $subject = $data['subject'] ?? 'No Subject';
    $body = $data['body'] ?? '';
    $recipients = $data['recipients'] ?? []; // Array of email addresses

    $headers = "From: CRM System <noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ">\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $success_count = 0;
    foreach ($recipients as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (@mail($email, $subject, $body, $headers)) {
                $success_count++;
            }
        }
    }

    $messages = Storage::read('messages');
    $logEntry = [
        'id' => uniqid(),
        'date' => date('Y-m-d H:i:s'),
        'subject' => $subject,
        'body' => $body,
        'recipients_count' => count($recipients),
        'status' => 'Sent (Simulated)'
    ];
    $messages[] = $logEntry;
    Storage::save('messages', $messages);
    Storage::log("Bulk mailing sent: $subject to $success_count recipients");

    echo json_encode(['success' => true, 'sent' => $success_count]);
}
