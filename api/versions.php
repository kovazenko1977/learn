<?php
Auth::requireRole(['admin', 'director', 'senior_admin']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $entity = isset($_GET['entity']) ? $_GET['entity'] : null;
    $id = isset($_GET['id']) ? $_GET['id'] : null;

    if (!$entity || !$id) {
        echo json_encode(['error' => 'Missing entity or ID']);
        exit;
    }

    $versionPath = __DIR__ . '/../storage/versions/' . $entity . '/' . $id . '/';
    if (!is_dir($versionPath)) {
        echo json_encode([]);
        exit;
    }

    $files = glob($versionPath . 'v*.json');
    $versions = [];
    foreach ($files as $file) {
        $v = json_decode(file_get_contents($file), true);
        if ($v) {
            $v['entity'] = $entity;
            $versions[] = $v;
        }
    }

    // Sort by version descending
    usort($versions, fn($a, $b) => $b['version'] - $a['version']);

    echo json_encode($versions);
}
