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

// View Mode and Settings from Section
$viewMode = $section['view_type'] ?? 'cards';
$itemsPerPage = (int)($section['items_per_page'] ?? 10);
$currentPage = (int)($_GET['page'] ?? 1);

$totalItems = count($newsItems);
$totalPages = ceil($totalItems / $itemsPerPage);
$offset = ($currentPage - 1) * $itemsPerPage;

$newsItems = array_slice($newsItems, $offset, $itemsPerPage);

// Increment views for displayed items
if ($currentPage === 1 && !isset($_GET['nocount'])) {
    foreach ($newsItems as $item) {
        NewsItem::incrementViews($item['id']);
    }
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: text/html; charset=UTF-8');

// Функция для замены относительных путей изображений на абсолютные
function getBaseUrl() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . "://" . $host . str_replace('/api', '', dirname($_SERVER['SCRIPT_NAME']));
    return rtrim($baseUrl, '/');
}

function makeUrlAbsolute($url) {
    if (empty($url)) return '';
    if (strpos($url, 'http') === 0 || strpos($url, '//') === 0 || strpos($url, 'data:') === 0) {
        return $url;
    }
    return getBaseUrl() . '/' . ltrim($url, '/');
}

function makeUrlsAbsolute($html) {
    $baseUrl = getBaseUrl();
    return preg_replace_callback('/(src|href)=["\']([^"\']+)["\']/', function($matches) use ($baseUrl) {
        $attr = $matches[1];
        $url = $matches[2];
        if (strpos($url, 'http') !== 0 && strpos($url, '//') !== 0 && strpos($url, 'data:') !== 0) {
            $url = $baseUrl . '/' . ltrim($url, '/');
        }
        return "$attr=\"$url\"";
    }, $html);
}

function calculateReadingTime($content) {
    $words = str_word_count(strip_tags($content));
    $minutes = ceil($words / 200);
    return $minutes > 0 ? $minutes : 1;
}
?>
<div class="news-section-wrapper" id="news-section-<?php echo htmlspecialchars($sectionId); ?>">
    <?php if ($viewMode === 'cards'): ?>
        <div class="news-cards-grid">
            <?php foreach ($newsItems as $item): ?>
                <article class="news-card">
                    <?php if (!empty($item['thumbnail'])): ?>
                        <div class="news-card-img-wrap">
                            <img src="<?php echo makeUrlAbsolute($item['thumbnail']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="news-card-body">
                        <h3 class="news-card-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <div class="news-card-excerpt">
                            <?php
                                $excerpt = strip_tags($item['content']);
                                echo mb_substr($excerpt, 0, 150) . (mb_strlen($excerpt) > 150 ? '...' : '');
                            ?>
                        </div>
                        <div class="news-card-footer">
                            <span class="news-date"><?php echo date('d.m.Y', strtotime($item['publish_at'] ?? $item['created_at'])); ?></span>
                            <span class="news-reading-time"><i class="bi bi-clock"></i> <?php echo calculateReadingTime($item['content']); ?> мин.</span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="news-table-wrap">
            <table class="news-table">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Заголовок</th>
                        <th>Просмотры</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($newsItems as $item): ?>
                        <tr>
                            <td class="news-date-cell"><?php echo date('d.m.Y', strtotime($item['publish_at'] ?? $item['created_at'])); ?></td>
                            <td class="news-title-cell"><?php echo htmlspecialchars($item['title']); ?></td>
                            <td class="news-views-cell"><?php echo $item['views'] ?? 0; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <nav class="news-pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?id=<?php echo $sectionId; ?>&page=<?php echo $i; ?>" class="news-page-link <?php echo $i === $currentPage ? 'active' : ''; ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</div>

<style>
    .news-section-wrapper { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.5; color: #333; }
    .news-cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .news-card { background: #fff; border: 1px solid #eee; border-radius: 12px; overflow: hidden; transition: box-shadow 0.3s; }
    .news-card:hover { box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
    .news-card-img-wrap { height: 180px; overflow: hidden; }
    .news-card-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
    .news-card-body { padding: 20px; }
    .news-card-title { margin: 0 0 10px; font-size: 1.25rem; font-weight: 600; color: #111; }
    .news-card-excerpt { font-size: 0.95rem; color: #666; margin-bottom: 15px; min-height: 3em; }
    .news-card-footer { display: flex; justify-content: space-between; font-size: 0.85rem; color: #999; }

    .news-table-wrap { overflow-x: auto; margin-bottom: 30px; }
    .news-table { width: 100%; border-collapse: collapse; }
    .news-table th { text-align: left; background: #f8f9fa; padding: 12px 15px; font-weight: 600; border-bottom: 2px solid #eee; }
    .news-table td { padding: 12px 15px; border-bottom: 1px solid #eee; }
    .news-date-cell { white-space: nowrap; width: 120px; color: #888; }
    .news-title-cell { font-weight: 500; }
    .news-views-cell { text-align: center; width: 100px; color: #999; }

    .news-pagination { display: flex; gap: 8px; justify-content: center; }
    .news-page-link { padding: 8px 15px; border: 1px solid #ddd; border-radius: 8px; text-decoration: none; color: #333; transition: all 0.2s; }
    .news-page-link:hover { background: #f0f0f0; }
    .news-page-link.active { background: #4facfe; color: #fff; border-color: #4facfe; }
</style>
