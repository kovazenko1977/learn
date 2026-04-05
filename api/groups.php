<?php
require_once __DIR__ . '/../includes/Auth.php';
Auth::requireAuth();

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$groups_file = __DIR__ . '/../data/groups.json';

// Initialize file if not exists
if (!file_exists($groups_file)) {
    file_put_contents($groups_file, json_encode([]));
}

function getGroups() {
    global $groups_file;
    if (!file_exists($groups_file)) return [];
    $content = file_get_contents($groups_file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function saveGroups($groups) {
    global $groups_file;
    $json = json_encode($groups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json !== false && json_last_error() === JSON_ERROR_NONE) {
        file_put_contents($groups_file, $json, LOCK_EX);
        return true;
    }
    return false;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(getGroups());
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $groups = getGroups();

    if (empty($data['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Name is required']);
        exit;
    }

    $new_group = [
        'id' => uniqid(),
        'name' => $data['name'],
        'description' => $data['description'] ?? ''
    ];

    $groups[] = $new_group;
    saveGroups($groups);
    echo json_encode($new_group);
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $groups = getGroups();
    $updated = false;

    foreach ($groups as &$group) {
        if ($group['id'] === $data['id']) {
            $group['name'] = $data['name'] ?? $group['name'];
            $group['description'] = $data['description'] ?? $group['description'];
            $updated = true;
            echo json_encode($group);
            break;
        }
    }
    unset($group);

    if ($updated) {
        saveGroups($groups);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Group not found']);
    }
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    $groups = getGroups();
    $new_groups = array_filter($groups, function($g) use ($id) {
        return $g['id'] !== $id;
    });

    if (count($new_groups) < count($groups)) {
        saveGroups(array_values($new_groups));
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Group not found']);
    }
} else {
    http_response_code(405);
}
