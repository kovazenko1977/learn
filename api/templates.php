<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/storage.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$templates_file = __DIR__ . '/../data/templates.php';
$templates = loadData($templates_file) ?? [];

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        echo json_encode($templates);
        break;
    case 'POST':
        $template = [
            'id' => time(),
            'name' => $data['name'] ?? 'Untitled',
            'subject' => $data['subject'] ?? '',
            'body' => $data['body'] ?? ''
        ];
        $templates[] = $template;
        saveData($templates_file, $templates);
        echo json_encode(['success' => true, 'id' => $template['id']]);
        break;
    case 'PUT':
        $id = $data['id'] ?? 0;
        $updated = false;
        foreach ($templates as &$t) {
            if ($t['id'] == $id) {
                $t['name'] = $data['name'] ?? $t['name'];
                $t['subject'] = $data['subject'] ?? $t['subject'];
                $t['body'] = $data['body'] ?? $t['body'];
                $updated = true;
                break;
            }
        }
        if ($updated) {
            saveData($templates_file, $templates);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Template not found']);
        }
        break;
    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        $filtered = array_filter($templates, function($t) use ($id) {
            return $t['id'] != $id;
        });
        if (count($filtered) !== count($templates)) {
            saveData($templates_file, array_values($filtered));
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Template not found']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
