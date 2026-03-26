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
foreach ($formConfig['fields'] as $field) {
    $fieldName = $field['name'];
    $val = $data[$fieldName] ?? '';
    if ($field['required'] && empty($val)) {
        http_response_code(400);
        echo json_encode(['error' => 'Field ' . $field['label'] . ' is required']);
        exit;
    }
    $submissionData[$field['label']] = $val;
}

// Prepare email content
$recipient = $formConfig['recipient'] ?: 'admin@example.com';
$subject = $formConfig['subject'] ?: 'New Booking Request';
$message = "New booking request received:\n\n";
foreach ($submissionData as $label => $val) {
    $message .= "$label: $val\n";
}

// Log submission (as a fallback since we don't have a real SMTP server here)
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

$headers = [
    'From: "Zhanna Booking" <' . ($settings['smtp_user'] ?? 'no-reply@zhanna-booking.site') . '>',
    'Reply-To: ' . ($data['email'] ?? ($settings['smtp_user'] ?? 'no-reply@zhanna-booking.site')),
    'X-Mailer: PHP/' . phpversion(),
    'Content-Type: text/plain; charset=utf-8'
];

// Note: In a headless sandbox without an MTA, mail() might fail,
// so we also log the "attempt" as success if logs are written.
$mailSent = @mail($recipient, $subject, $message, implode("\r\n", $headers));

echo json_encode(['success' => true, 'mail_sent' => $mailSent, 'message' => 'Ваша заявка успешно обработана']);
