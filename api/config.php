<?php
require_once '../includes/Storage.php';
require_once '../includes/AuthManager.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$action = $_GET['action'] ?? '';
$type = $_GET['type'] ?? 'forms'; // 'forms', 'settings', 'mailing_contacts', etc.
$storage = new Storage($type . '.json');

// Public access: only GET 'forms' with a specific 'id'
// All other actions and types require authentication
$isAuthenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

if (!$isAuthenticated) {
    if ($action !== 'get' || $type !== 'forms' || !isset($_GET['id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

switch ($action) {
    case 'get':
        $data = $storage->read();
        if ($type === 'forms' && isset($_GET['id'])) {
            $formId = $_GET['id'];
            if (empty($formId)) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing form id']);
                exit;
            }
            $found = null;
            foreach ($data as $f) {
                if ($f['id'] === $formId) {
                    $found = $f;
                    break;
                }
            }
            if ($found) {
                if (!$isAuthenticated) {
                    unset($found['recipient'], $found['subject']);
                }
                echo json_encode($found);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Form not found']);
            }
        } else {
            // Return all data (only for authenticated users or if type is not forms+id)
            echo json_encode($data);
        }
        break;

    case 'save':
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data !== null) {
            if ($storage->write($data)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to write data']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
