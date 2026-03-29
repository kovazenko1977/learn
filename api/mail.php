<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$settingsStorage = new Storage('settings');
$settings = $settingsStorage->read();

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['recipients'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid mailing data']);
        exit;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $settings['smtp_host'] ?? 'localhost';
        $mail->SMTPAuth   = !empty($settings['smtp_pass']);
        $mail->Username   = $settings['smtp_user'] ?? '';
        $mail->Password   = $settings['smtp_pass'] ?? '';
        $mail->SMTPSecure = $settings['smtp_secure'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $settings['smtp_port'] ?? 587;
        $mail->setFrom($settings['from_email'] ?? 'noreply@example.com', $settings['from_name'] ?? 'Mailer');

        $mail->isHTML(true);
        $mail->Subject = $input['subject'] ?? 'No Subject';

        // Attachments - Security Refinement (basename)
        if (isset($input['attachments']) && is_array($input['attachments'])) {
            foreach ($input['attachments'] as $fileName) {
                $sanitizedFileName = basename($fileName);
                $filePath = __DIR__ . '/../uploads/' . $sanitizedFileName;
                if (file_exists($filePath)) {
                    $mail->addAttachment($filePath);
                }
            }
        }

        $results = [];
        $interval = (int)($settings['interval_sec'] ?? 5);

        foreach ($input['recipients'] as $index => $recipient) {
            $mail->clearAddresses();
            $mail->addAddress($recipient['email'], $recipient['name'] ?? '');

            $body = $input['body'] ?? '';
            foreach ($recipient as $key => $value) {
                if (is_string($value)) {
                    $body = str_replace('{{' . $key . '}}', $value, $body);
                }
            }
            $mail->Body = $body;

            try {
                if (isset($input['simulate']) && $input['simulate'] === true) {
                    $results[] = ['email' => $recipient['email'], 'status' => 'sent (simulated)'];
                } else {
                    $mail->send();
                    $results[] = ['email' => $recipient['email'], 'status' => 'sent'];
                }
            } catch (Exception $e) {
                $results[] = ['email' => $recipient['email'], 'status' => 'failed', 'error' => $mail->ErrorInfo];
            }

            // Sleep only if sending small batches - large lists should use async logic
            if ($index < count($input['recipients']) - 1 && $interval > 0) {
                sleep($interval);
            }
        }

        echo json_encode(['status' => 'success', 'results' => $results]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
