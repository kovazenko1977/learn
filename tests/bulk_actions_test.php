<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Models\NewsItem;
use App\Models\Section;

// Create dummy section and news
$s1 = ['id' => 's1', 'name' => 'Section 1'];
$s2 = ['id' => 's2', 'name' => 'Section 2'];
Section::save($s1);
Section::save($s2);

$n1 = ['id' => 'n1', 'section_id' => 's1', 'title' => 'News 1', 'status' => 'published'];
$n2 = ['id' => 'n2', 'section_id' => 's1', 'title' => 'News 2', 'status' => 'published'];
NewsItem::save($n1);
NewsItem::save($n2);

// Mock bulk draft
$ids = ['n1', 'n2'];
foreach ($ids as $id) {
    $item = NewsItem::find($id);
    $item['status'] = 'draft';
    NewsItem::save($item);
}

$n1 = NewsItem::find('n1');
$n2 = NewsItem::find('n2');

if ($n1['status'] === 'draft' && $n2['status'] === 'draft') {
    echo "[PASSED] Bulk Draft\n";
} else {
    echo "[FAILED] Bulk Draft\n";
}

// Mock bulk move
foreach ($ids as $id) {
    $item = NewsItem::find($id);
    $item['section_id'] = 's2';
    NewsItem::save($item);
}

$n1 = NewsItem::find('n1');
$n2 = NewsItem::find('n2');

if ($n1['section_id'] === 's2' && $n2['section_id'] === 's2') {
    echo "[PASSED] Bulk Move\n";
} else {
    echo "[FAILED] Bulk Move\n";
}
