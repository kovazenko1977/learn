<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

Auth::requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$entries = Storage::read('entries.json');

switch ($method) {
    case 'GET':
        echo json_encode($entries);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['title'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Invalid data']);
            break;
        }
        $newEntry = [
            'id' => uniqid(),
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'date' => $data['date'] ?? date('Y-m-d H:i'),
            'reminder' => $data['reminder'] ?? null,
            'created_at' => date('c')
        ];
        $entries[] = $newEntry;
        Storage::write('entries.json', $entries);
        echo json_encode($newEntry);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['id'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Invalid data']);
            break;
        }
        $updated = false;
        foreach ($entries as &$entry) {
            if ($entry['id'] === $data['id']) {
                $entry['title'] = $data['title'] ?? $entry['title'];
                $entry['description'] = $data['description'] ?? $entry['description'];
                $entry['date'] = $data['date'] ?? $entry['date'];
                $entry['reminder'] = $data['reminder'] ?? $entry['reminder'];
                $updated = true;
                break;
            }
        }
        if ($updated) {
            Storage::write('entries.json', $entries);
            echo json_encode(['success' => true]);
        } else {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Entry not found']);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        $newEntries = array_filter($entries, function($e) use ($id) {
            return $e['id'] !== $id;
        });
        if (count($newEntries) !== count($entries)) {
            Storage::write('entries.json', array_values($newEntries));
            echo json_encode(['success' => true]);
        } else {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Entry not found']);
        }
        break;

    default:
        header('HTTP/1.1 405 Method Not Allowed');
        break;
}
