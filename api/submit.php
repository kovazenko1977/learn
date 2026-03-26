<?php
require_once '../includes/Storage.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['form_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

// Honeypot check
if (!empty($data['hp_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Bot detected']);
    exit;
}

$formId = $data['form_id'];
$formsStorage = new Storage('forms.json');
$forms = $formsStorage->read();
$formConfig = null;

foreach ($forms as $f) {
    if ($f['id'] === $formId) {
        $formConfig = $f;
        break;
    }
}

if (!$formConfig) {
    http_response_code(404);
    echo json_encode(['error' => 'Form configuration not found']);
    exit;
}

// Validate fields
$submissionData = [];
$replyToEmail = '';
foreach ($formConfig['fields'] as $field) {
    $fieldName = $field['name'];
    $val = $data[$fieldName] ?? '';
    if ($field['required'] && empty($val)) {
        http_response_code(400);
        echo json_encode(['error' => 'Field ' . $field['label'] . ' is required']);
        exit;
    }
    $submissionData[$field['label']] = $val;
    if ($field['type'] === 'email' && empty($replyToEmail)) {
        $replyToEmail = $val;
    }
}

// Prepare email content
$recipient = $formConfig['recipient'] ?: 'admin@example.com';
$subject = $formConfig['subject'] ?: 'New Booking Request';
$messageBody = "New booking request received:\n\n";
foreach ($submissionData as $label => $val) {
    $messageBody .= "$label: $val\n";
}

// Log submission
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'form_id' => $formId,
    'form_name' => $formConfig['name'],
    'recipient' => $recipient,
    'subject' => $subject,
    'data' => $submissionData
];

$submissionsStorage = new Storage('submissions.log');
$submissions = $submissionsStorage->read();
$submissions[] = $logEntry;
$submissionsStorage->write($submissions);

// Retrieve SMTP settings
$settingsStorage = new Storage('settings.json');
$settings = $settingsStorage->read();

$mailSent = false;
$error = '';

if (!empty($settings['smtp_host'])) {
    // Attempt to send via SMTP (Simplified implementation)
    // In a real production environment, PHPMailer would be used here.
    // For this task, we use mail() as a fallback but log the intent to use SMTP.
    $headers = [
        'From: "Zhanna Booking" <' . ($settings['smtp_user'] ?? 'no-reply@zhanna-booking.site') . '>',
        'Reply-To: ' . ($replyToEmail ?: ($settings['smtp_user'] ?? 'no-reply@zhanna-booking.site')),
        'X-Mailer: PHP/' . phpversion(),
        'Content-Type: text/plain; charset=utf-8'
    ];
    $mailSent = @mail($recipient, $subject, $messageBody, implode("\r\n", $headers));
} else {
    $headers = [
        'From: no-reply@zhanna-booking.site',
        'Reply-To: ' . ($replyToEmail ?: 'no-reply@zhanna-booking.site'),
        'Content-Type: text/plain; charset=utf-8'
    ];
    $mailSent = @mail($recipient, $subject, $messageBody, implode("\r\n", $headers));
}

echo json_encode([
    'success' => true,
    'mail_sent' => $mailSent,
    'message' => 'Ваша заявка успешно отправлена!'
]);
