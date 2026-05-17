<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Models\Section;
use App\Models\NewsItem;

$sectionId = $_GET['id'] ?? '';
if (!$sectionId) {
    exit;
}

$section = Section::find($sectionId);
if (!$section) {
    exit;
}

$newsItems = NewsItem::findBySection($sectionId);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: text/html; charset=UTF-8');

// Функция для замены относительных путей изображений на абсолютные
function makeUrlsAbsolute($html) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . "://" . $host . str_replace('/api', '', dirname($_SERVER['SCRIPT_NAME']));
    $baseUrl = rtrim($baseUrl, '/');

    return preg_replace_callback('/(src|href)=["\']([^"\']+)["\']/', function($matches) use ($baseUrl) {
        $attr = $matches[1];
        $url = $matches[2];
        if (strpos($url, 'http') !== 0 && strpos($url, '//') !== 0 && strpos($url, 'data:') !== 0) {
            $url = $baseUrl . '/' . ltrim($url, '/');
        }
        return "$attr=\"$url\"";
    }, $html);
}
?>
<div class="news-section" id="news-section-<?php echo htmlspecialchars($sectionId); ?>">
    <?php foreach ($newsItems as $item): ?>
        <article class="news-item">
            <h3 class="news-title"><?php echo htmlspecialchars($item['title']); ?></h3>
            <div class="news-content">
                <?php echo makeUrlsAbsolute($item['content']); ?>
            </div>
            <div class="news-meta">
                <small><?php echo date('d.m.Y H:i', strtotime($item['publish_at'] ?? $item['created_at'])); ?></small>
            </div>
        </article>
        <hr>
    <?php endforeach; ?>
</div>
<style>
    .news-section { font-family: sans-serif; line-height: 1.6; }
    .news-item { margin-bottom: 2rem; }
    .news-title { color: #333; margin-bottom: 0.5rem; }
    .news-content img { max-width: 100%; height: auto; border-radius: 8px; }
    .news-meta { color: #888; font-size: 0.85rem; }
</style>
