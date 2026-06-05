<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Models\NewsItem;

// Create news with metadata
$id = 'meta_test_' . uniqid();
$data = [
    'id' => $id,
    'section_id' => 's1',
    'title' => 'Meta Test',
    'content' => 'Content',
    'status' => 'published',
    'reactions' => ['user1', 'user2'],
    'view_logs' => ['ip1', 'ip2'],
    'reaction_count' => 2,
    'views' => 2
];
NewsItem::save($data);

// Simulate edit from admin/news.php
$_POST['id'] = $id;
$_POST['section_id'] = 's1';
$_POST['title'] = 'Meta Test Edited';
$_POST['content'] = 'Content Edited';
$_POST['status'] = 'published';
$_POST['views'] = 2;
$_POST['reaction_count'] = 2;

// Replicate logic from admin/news.php
$existing = NewsItem::find($id);
$newData = [
    'id' => $id,
    'section_id' => $_POST['section_id'],
    'title' => $_POST['title'],
    'content' => $_POST['content'],
    'status' => $_POST['status'],
    'updated_at' => date('Y-m-d H:i:s'),
    'views' => (int)$_POST['views'],
    'reaction_count' => (int)$_POST['reaction_count'],
    'reactions' => $existing['reactions'] ?? [],
    'view_logs' => $existing['view_logs'] ?? []
];
NewsItem::save($newData);

$saved = NewsItem::find($id);

if ($saved['reactions'] === ['user1', 'user2'] && $saved['view_logs'] === ['ip1', 'ip2']) {
    echo "[PASSED] Data Integrity Check\n";
} else {
    echo "[FAILED] Data Integrity Check\n";
    print_r($saved);
}
