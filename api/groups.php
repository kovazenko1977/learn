<?php
require_once __DIR__ . '/../includes/Security.php';

Security::checkAuth();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$groupsFile = __DIR__ . '/../data/groups.json';
$filesDataFile = __DIR__ . '/../data/files.json';

if ($action === 'list') {
    $groups = json_decode(file_get_contents($groupsFile), true) ?: ["Общее"];
    echo json_encode($groups);
} elseif ($action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $newGroup = trim($data['name'] ?? '');
    if (!$newGroup) {
        echo json_encode(['error' => 'Invalid group name']);
        exit;
    }
    $groups = json_decode(file_get_contents($groupsFile), true) ?: ["Общее"];
    if (in_array($newGroup, $groups)) {
        echo json_encode(['error' => 'Group already exists']);
        exit;
    }
    $groups[] = $newGroup;
    file_put_contents($groupsFile, json_encode(array_values($groups), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    Security::log("Group added: $newGroup");
    echo json_encode(['success' => true]);
} elseif ($action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $targetGroup = $data['name'] ?? '';
    if ($targetGroup === 'Общее') {
        echo json_encode(['error' => 'Cannot delete default group']);
        exit;
    }

    $groups = json_decode(file_get_contents($groupsFile), true) ?: ["Общее"];
    $key = array_search($targetGroup, $groups);
    if ($key === false) {
        echo json_encode(['error' => 'Group not found']);
        exit;
    }

    // Delete group
    array_splice($groups, $key, 1);
    file_put_contents($groupsFile, json_encode(array_values($groups), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    // Reassign files to "Общее"
    $files = json_decode(file_get_contents($filesDataFile), true) ?: [];
    foreach ($files as &$file) {
        if (($file['group'] ?? '') === $targetGroup) {
            $file['group'] = 'Общее';
        }
    }
    file_put_contents($filesDataFile, json_encode($files, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    Security::log("Group deleted: $targetGroup. Files reassigned to Общее.");
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Invalid action']);
}
