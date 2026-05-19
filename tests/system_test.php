<?php
require_once __DIR__ . '/../src/autoload.php';

use App\Helpers\Auth;
use App\Models\Section;
use App\Models\NewsItem;

function test($name, $fn) {
    try {
        $fn();
        echo "[PASSED] $name\n";
    } catch (Exception $e) {
        echo "[FAILED] $name: " . $e->getMessage() . "\n";
    }
}

test("Auth logic", function() {
    // Re-initialize user store
    $userStore = Auth::getUserStore();
    $users = $userStore->getAll();
    $admin = $users[0];

    Auth::updatePin("111111", $admin['id']);
    if (!Auth::login("111111")) throw new Exception("Login failed with new PIN");
    if (Auth::login("000000")) throw new Exception("Wrong PIN login allowed");
});

test("Section CRUD", function() {
    Section::save(['id' => 'test-section', 'name' => 'Test Section']);
    $s = Section::find('test-section');
    if ($s['name'] !== 'Test Section') throw new Exception("Section save/find failed");
    Section::delete('test-section');
    if (Section::find('test-section') !== null) throw new Exception("Section delete failed");
});

test("NewsItem Scheduling", function() {
    Section::save(['id' => 'news-test', 'name' => 'News Test']);
    NewsItem::save(['id' => 'n1', 'section_id' => 'news-test', 'title' => 'Past', 'content' => '...', 'publish_at' => date('Y-m-d H:i:s', time() - 3600)]);
    NewsItem::save(['id' => 'n2', 'section_id' => 'news-test', 'title' => 'Future', 'content' => '...', 'publish_at' => date('Y-m-d H:i:s', time() + 3600)]);

    $items = NewsItem::findBySection('news-test', true);
    if (count($items) !== 1) throw new Exception("Scheduling filter failed: expected 1, got " . count($items));
    if ($items[0]['title'] !== 'Past') throw new Exception("Wrong item returned");
});

echo "ALL TESTS COMPLETED\n";
