<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/auth.php';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') require_auth();

header('Content-Type: application/json');

$storage = new Storage('settings');
$data = $storage->read();

if (empty($data)) {
    $data = [
        // SMTP SERVER (7)
        'smtp_host' => 'smtp.example.com',
        'smtp_port' => 587,
        'smtp_user' => 'user@example.com',
        'smtp_pass' => '',
        'smtp_secure' => 'tls',
        'smtp_timeout_sec' => 30,
        'enable_smtp_keepalive' => 'false',

        // SENDING LIMITS (6)
        'interval_sec' => 5,
        'batch_size' => 10,
        'max_per_hour' => 100,
        'max_per_day' => 1000,
        'retry_attempts' => 3,
        'retry_delay_sec' => 30,

        // IDENTITY (5)
        'from_name' => 'My Business',
        'from_email' => 'noreply@example.com',
        'reply_to' => 'support@example.com',
        'organization_name' => 'My Corp',
        'sender_phone' => '+1234567890',

        // ADVANCED HEADERS (8)
        'email_priority' => 'Normal',
        'x_mailer_header' => 'DocMailer v1.0',
        'list_unsubscribe_url' => '',
        'precedence_header' => 'bulk',
        'auto_submitted_header' => 'auto-generated',
        'message_id_domain' => 'example.com',
        'content_type' => 'text/html',
        'charset' => 'UTF-8',

        // DKIM (4)
        'dkim_selector' => '',
        'dkim_passphrase' => '',
        'dkim_identity' => '',
        'dkim_domain' => '',

        // TRACKING & LOGS (6)
        'enable_logs' => 'true',
        'log_retention_days' => 30,
        'track_opens' => 'false',
        'track_clicks' => 'false',
        'tracking_domain' => '',
        'debug_mode' => 'false',

        // SECURITY (6)
        'app_passcode' => '123456',
        'session_timeout_min' => 60,
        'allowed_upload_extensions' => 'pdf,docx,txt,jpg,png,zip,csv',
        'max_upload_size_mb' => 10,
        'use_ssl_api' => 'true',
        'cors_policy' => 'same-origin',

        // INTERFACE & UI (8)
        'app_theme' => 'light',
        'ui_accent_color' => '#7360f2',
        'default_language' => 'en',
        'show_stats_on_dashboard' => 'true',
        'enable_toasts' => 'true',
        'toast_duration_ms' => 3000,
        'sidebar_collapsed' => 'false',
        'compact_mode' => 'false',

        // NOTIFICATIONS (6)
        'notify_on_complete' => 'true',
        'notify_email' => 'admin@example.com',
        'notify_on_failure' => 'true',
        'notify_webhook_url' => '',
        'desktop_notifications' => 'false',
        'sound_alerts' => 'true'
    ];
    $storage->write($data);
}

if ($method === 'GET') {
    if (!is_authenticated()) {
        echo json_encode([
            'app_theme' => $data['app_theme'] ?? 'light',
            'ui_accent_color' => $data['ui_accent_color'] ?? '#7360f2'
        ]);
    } else {
        echo json_encode($data);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $updatedData = array_merge($data, $input);
        $storage->write($updatedData);
        echo json_encode(['status' => 'success', 'settings' => $updatedData]);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    }
}
