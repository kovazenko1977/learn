<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/storage.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$emails_file = __DIR__ . '/../data/emails.php';
$emails = loadData($emails_file) ?? [];

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        echo json_encode($emails);
        break;
    case 'POST':
        $email = $data['email'] ?? '';
        $note = $data['note'] ?? '';
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Check if already exists
            $exists = false;
            foreach ($emails as $e) {
                if ($e['email'] === $email) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $emails[] = ['email' => $email, 'note' => $note];
                saveData($emails_file, $emails);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid email']);
        }
        break;
    case 'DELETE':
        $email = $_GET['email'] ?? '';
        $filtered = array_filter($emails, function($e) use ($email) {
            return $e['email'] !== $email;
        });
        if (count($filtered) !== count($emails)) {
            saveData($emails_file, array_values($filtered));
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Email not found']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
