<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

Auth::requireRole(['superadmin', 'admin_communications']);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'send':
        $data = json_decode(file_get_contents('php://input'), true);
        $to = $data['to'] ?? '';
        $subject = $data['subject'] ?? '';
        $body = $data['body'] ?? '';

        // Simplified email sending simulation.
        // In production, use PHPMailer or an equivalent library.
        Security::log('send_mail', $_SESSION['user_id'], 'mail', ['to' => $to, 'subject' => $subject]);

        // Mocking successful email dispatch.
        echo json_encode(['success' => true, 'message' => 'Email notification sent']);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
