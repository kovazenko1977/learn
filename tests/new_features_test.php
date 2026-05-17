<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Models\NewsItem;
use App\Models\Section;

// Test View Increment
$sec = ['id' => 'test_v', 'name' => 'View Test'];
Section::save($sec);
$item = [
    'id' => 'test_item_v',
    'section_id' => 'test_v',
    'title' => 'Test Item',
    'content' => 'Test content',
    'views' => 10
];
NewsItem::save($item);

NewsItem::incrementViews('test_item_v', 'test_visitor');
$updated = NewsItem::find('test_item_v');
if ($updated['views'] === 11) {
    echo "[PASSED] View Increment\n";
} else {
    echo "[FAILED] View Increment: expected 11, got " . $updated['views'] . "\n";
}

// Test Pinning Logic
$item2 = [
    'id' => 'test_item_p',
    'section_id' => 'test_v',
    'title' => 'Pinned Item',
    'content' => 'Test content',
    'is_pinned' => true,
    'created_at' => '2020-01-01 00:00:00'
];
NewsItem::save($item2);

$items = NewsItem::findBySection('test_v', false);
if ($items[0]['id'] === 'test_item_p') {
    echo "[PASSED] Pinning Order\n";
} else {
    echo "[FAILED] Pinning Order: pinned item should be first\n";
}

// Cleanup
NewsItem::delete('test_item_v');
NewsItem::delete('test_item_p');
Section::delete('test_v');
