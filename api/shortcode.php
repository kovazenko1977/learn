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

// Security: IP/UserAgent Hash for unique views/reactions
$visitorHash = md5($_SERVER['REMOTE_ADDR'] . ($_SERVER['HTTP_USER_AGENT'] ?? ''));

// Handle Reactions API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'reaction') {
    $newsId = $_POST['news_id'] ?? '';
    if ($newsId) {
        header('Content-Type: application/json');
        echo json_encode(NewsItem::toggleReaction($newsId, $visitorHash));
        exit;
    }
}

$newsItems = NewsItem::findBySection($sectionId);

// Apply Filters (Search, Tags)
$search = $_GET['q'] ?? '';
$tag = $_GET['tag'] ?? '';

if ($search) {
    $newsItems = array_filter($newsItems, function($item) use ($search) {
        return mb_stripos($item['title'], $search) !== false || mb_stripos(strip_tags($item['content']), $search) !== false;
    });
}

if ($tag) {
    $newsItems = array_filter($newsItems, function($item) use ($tag) {
        return in_array($tag, $item['tags'] ?? []);
    });
}

// Apply Sorting
$sortBy = $section['sort_by'] ?? 'date_desc';
usort($newsItems, function($a, $b) use ($sortBy) {
    switch ($sortBy) {
        case 'date_asc': return ($a['publish_at'] ?? $a['created_at']) <=> ($b['publish_at'] ?? $b['created_at']);
        case 'views_desc': return ($b['views'] ?? 0) <=> ($a['views'] ?? 0);
        case 'reactions_desc': return ($b['reaction_count'] ?? 0) <=> ($a['reaction_count'] ?? 0);
        case 'date_desc':
        default:
            return ($b['publish_at'] ?? $b['created_at']) <=> ($a['publish_at'] ?? $a['created_at']);
    }
});

// Single News Item View Mode
$singleNewsId = $_GET['news_id'] ?? '';
$singleItem = null;
if ($singleNewsId) {
    $singleItem = NewsItem::find($singleNewsId);
    if ($singleItem && $singleItem['section_id'] === $sectionId) {
        NewsItem::incrementViews($singleNewsId, $visitorHash);
    } else {
        $singleItem = null;
    }
}

// View Mode and Settings from Section
$viewMode = $section['view_type'] ?? 'cards';
$itemsPerPage = (int)($section['items_per_page'] ?? 10);
$currentPage = (int)($_GET['page'] ?? 1);

$totalItems = count($newsItems);
$totalPages = ceil($totalItems / $itemsPerPage);
$offset = ($currentPage - 1) * $itemsPerPage;

if (!$singleItem) {
    $newsItems = array_slice($newsItems, $offset, $itemsPerPage);
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: text/html; charset=UTF-8');

// External Assets (Bootstrap Icons)
echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">';

// Lightbox Script for images
?>
<script>
if (!window.newsLightboxInjected) {
    window.newsLightboxInjected = true;
    document.addEventListener('click', function(e) {
        if (e.target.tagName === 'IMG' && (e.target.closest('.news-content') || e.target.closest('.news-single-img'))) {
            const overlay = document.createElement('div');
            overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:99999;display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
            const img = document.createElement('img');
            img.src = e.target.src;
            img.style.cssText = 'max-width:90%;max-height:90%;border-radius:10px;box-shadow:0 0 30px rgba(0,0,0,0.5);';
            overlay.appendChild(img);
            overlay.onclick = () => overlay.remove();
            document.body.appendChild(overlay);
        }
    });
}
</script>
<?php

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
    <?php if ($section['custom_css']): ?>
        <style><?php echo $section['custom_css']; ?></style>
    <?php endif; ?>

    <?php if (!$singleItem && ($section['show_search'] ?? true)): ?>
        <div class="news-search-bar">
            <input type="text" class="news-search-input" placeholder="<?php echo htmlspecialchars($section['lang_search_placeholder'] ?? 'Поиск...'); ?>" value="<?php echo htmlspecialchars($search); ?>" data-section="<?php echo $sectionId; ?>">
        </div>
    <?php endif; ?>

    <?php if ($singleItem): ?>
        <!-- Single News View -->
        <article class="news-single">
            <a href="#" class="news-back-link" data-section="<?php echo $sectionId; ?>">&larr; Назад к списку</a>
            <?php if ($section['show_title'] ?? true): ?>
                <h1 class="news-single-title"><?php echo htmlspecialchars($singleItem['title']); ?></h1>
            <?php endif; ?>

            <div class="news-meta mb-4">
                <?php if ($section['show_date'] ?? true): ?>
                    <span class="news-meta-item"><i class="bi bi-calendar3"></i> <?php echo date('d.m.Y', strtotime($singleItem['publish_at'] ?? $singleItem['created_at'])); ?></span>
                <?php endif; ?>
                <?php if (($section['show_author'] ?? false) && !empty($singleItem['author'])): ?>
                    <span class="news-meta-item"><i class="bi bi-person"></i> <?php echo htmlspecialchars($singleItem['author']); ?></span>
                <?php endif; ?>
                <?php if ($section['show_views'] ?? true): ?>
                    <span class="news-meta-item"><i class="bi bi-eye"></i> <?php echo $singleItem['views'] ?? 0; ?></span>
                <?php endif; ?>
                <?php if ($section['show_reading_time'] ?? true): ?>
                    <span class="news-meta-item"><i class="bi bi-clock"></i> <?php echo calculateReadingTime($singleItem['content']); ?> мин.</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($singleItem['thumbnail'])): ?>
                <div class="news-single-img">
                    <img src="<?php echo makeUrlAbsolute($singleItem['thumbnail']); ?>" alt="">
                </div>
            <?php endif; ?>

            <div class="news-content">
                <?php echo makeUrlsAbsolute($singleItem['content']); ?>
            </div>

            <?php if (($section['show_tags'] ?? true) && !empty($singleItem['tags'])): ?>
                <div class="news-tags mt-4">
                    <?php foreach ($singleItem['tags'] as $t): ?>
                        <span class="news-tag" data-tag="<?php echo htmlspecialchars($t); ?>" data-section="<?php echo $sectionId; ?>">#<?php echo htmlspecialchars($t); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="news-single-footer mt-5 pt-4 border-top d-flex justify-content-between align-items-center">
                <?php if ($section['show_reactions'] ?? true):
                    $isLiked = in_array($visitorHash, $singleItem['reactions'] ?? []);
                ?>
                    <button class="news-reaction-btn <?php echo $isLiked ? 'active' : ''; ?>" data-id="<?php echo $singleItem['id']; ?>" data-section="<?php echo $sectionId; ?>">
                        <i class="bi <?php echo $isLiked ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                        <span class="reaction-count"><?php echo $singleItem['reaction_count'] ?? 0; ?></span>
                    </button>
                <?php endif; ?>

                <?php if ($section['show_share'] ?? true):
                    $shareUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                    $shareTitle = urlencode($singleItem['title']);
                ?>
                    <div class="news-share-btns">
                        <a href="https://t.me/share/url?url=<?php echo urlencode($shareUrl); ?>&text=<?php echo $shareTitle; ?>" target="_blank" class="share-btn tg"><i class="bi bi-telegram"></i></a>
                        <a href="https://wa.me/?text=<?php echo $shareTitle . '%20' . urlencode($shareUrl); ?>" target="_blank" class="share-btn wa"><i class="bi bi-whatsapp"></i></a>
                    </div>
                <?php endif; ?>
            </div>
        </article>

    <?php elseif ($viewMode === 'cards'): ?>
        <div class="news-cards-grid">
            <?php foreach ($newsItems as $item): ?>
                <article class="news-card">
                    <?php if (!empty($item['thumbnail'])): ?>
                        <div class="news-card-img-wrap" data-news-id="<?php echo $item['id']; ?>" data-section="<?php echo $sectionId; ?>" style="cursor: pointer;">
                            <img src="<?php echo makeUrlAbsolute($item['thumbnail']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="news-card-body">
                        <?php if ($section['show_title'] ?? true): ?>
                            <h3 class="news-card-title" data-news-id="<?php echo $item['id']; ?>" data-section="<?php echo $sectionId; ?>" style="cursor: pointer;"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <?php endif; ?>

                        <div class="news-card-excerpt">
                            <?php
                                $excerpt = strip_tags($item['content']);
                                echo mb_substr($excerpt, 0, 120) . (mb_strlen($excerpt) > 120 ? '...' : '');
                            ?>
                        </div>

                        <div class="news-card-footer">
                            <div class="news-card-meta">
                                <?php if ($section['show_date'] ?? true): ?>
                                    <span class="news-date"><?php echo date('d.m.Y', strtotime($item['publish_at'] ?? $item['created_at'])); ?></span>
                                <?php endif; ?>
                                <?php if ($section['show_views'] ?? true): ?>
                                    <span class="news-views"><i class="bi bi-eye"></i> <?php echo $item['views'] ?? 0; ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="#" class="news-more-btn" data-news-id="<?php echo $item['id']; ?>" data-section="<?php echo $sectionId; ?>"><?php echo htmlspecialchars($section['lang_read_more'] ?? 'Читать далее'); ?></a>
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
                        <?php if ($section['show_date'] ?? true): ?><th>Дата</th><?php endif; ?>
                        <?php if ($section['show_title'] ?? true): ?><th>Заголовок</th><?php endif; ?>
                        <?php if ($section['show_views'] ?? true): ?><th>Просмотры</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($newsItems as $item): ?>
                        <tr data-news-id="<?php echo $item['id']; ?>" data-section="<?php echo $sectionId; ?>" style="cursor: pointer;" class="news-table-row">
                            <?php if ($section['show_date'] ?? true): ?>
                                <td class="news-date-cell"><?php echo date('d.m.Y', strtotime($item['publish_at'] ?? $item['created_at'])); ?></td>
                            <?php endif; ?>
                            <?php if ($section['show_title'] ?? true): ?>
                                <td class="news-title-cell"><?php echo htmlspecialchars($item['title']); ?></td>
                            <?php endif; ?>
                            <?php if ($section['show_views'] ?? true): ?>
                                <td class="news-views-cell"><?php echo $item['views'] ?? 0; ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (!$singleItem && $totalPages > 1): ?>
        <nav class="news-pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="#" class="news-page-link <?php echo $i === $currentPage ? 'active' : ''; ?>" data-page="<?php echo $i; ?>" data-section="<?php echo $sectionId; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</div>

<style>
    .news-section-wrapper { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.5; color: #333; max-width: 1200px; margin: 0 auto; }
    .news-search-bar { margin-bottom: 25px; }
    .news-search-input { width: 100%; padding: 12px 20px; border: 1px solid #ddd; border-radius: 25px; outline: none; transition: border-color 0.3s; }
    .news-search-input:focus { border-color: #4facfe; }

    .news-cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .news-card { background: #fff; border: 1px solid #eee; border-radius: 15px; overflow: hidden; transition: transform 0.3s, box-shadow 0.3s; display: flex; flex-direction: column; }
    .news-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.08); }
    .news-card-img-wrap { height: 190px; overflow: hidden; }
    .news-card-img-wrap img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
    .news-card:hover .news-card-img-wrap img { transform: scale(1.05); }
    .news-card-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
    .news-card-title { margin: 0 0 12px; font-size: 1.2rem; font-weight: 700; color: #222; line-height: 1.3; }
    .news-card-excerpt { font-size: 0.9rem; color: #666; margin-bottom: 20px; flex-grow: 1; }
    .news-card-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f5f5f5; pt: 15px; }
    .news-card-meta { font-size: 0.8rem; color: #999; }
    .news-card-meta span { margin-right: 10px; }
    .news-more-btn { font-size: 0.85rem; color: #4facfe; text-decoration: none; font-weight: 600; }

    .news-table-wrap { overflow-x: auto; margin-bottom: 30px; background: #fff; border-radius: 12px; border: 1px solid #eee; }
    .news-table { width: 100%; border-collapse: collapse; }
    .news-table th { text-align: left; background: #fafafa; padding: 15px 20px; font-weight: 600; font-size: 0.9rem; color: #555; }
    .news-table td { padding: 15px 20px; border-top: 1px solid #eee; font-size: 0.95rem; }
    .news-table-row:hover { background: #f9f9f9; }
    .news-date-cell { color: #888; width: 130px; }
    .news-title-cell { font-weight: 600; color: #333; }

    .news-single-title { font-size: 2.5rem; font-weight: 800; margin-bottom: 15px; color: #111; }
    .news-back-link { display: inline-block; margin-bottom: 20px; color: #666; text-decoration: none; font-size: 0.9rem; }
    .news-back-link:hover { color: #4facfe; }
    .news-meta { display: flex; flex-wrap: wrap; gap: 15px; font-size: 0.9rem; color: #888; border-bottom: 1px solid #eee; padding-bottom: 20px; }
    .news-meta-item i { margin-right: 5px; color: #4facfe; }
    .news-single-img { margin: 30px 0; border-radius: 20px; overflow: hidden; max-height: 500px; }
    .news-single-img img { width: 100%; height: auto; object-fit: cover; }
    .news-content { font-size: 1.1rem; line-height: 1.7; color: #444; }
    .news-content img { max-width: 100%; height: auto; border-radius: 10px; margin: 15px 0; }

    .news-tags { display: flex; flex-wrap: wrap; gap: 8px; }
    .news-tag { background: #f0f2f5; padding: 5px 12px; border-radius: 15px; font-size: 0.8rem; color: #555; cursor: pointer; transition: background 0.2s; }
    .news-tag:hover { background: #e2e5e9; color: #4facfe; }

    .news-reaction-btn { background: #fff; border: 1px solid #eee; padding: 8px 18px; border-radius: 20px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px; }
    .news-reaction-btn:hover { background: #fff0f0; border-color: #ffbaba; }
    .news-reaction-btn.active { background: #fff0f0; border-color: #ffbaba; color: #f44336; }
    .news-reaction-btn.active i { color: #f44336; }

    .news-share-btns { display: flex; gap: 10px; }
    .share-btn { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; text-decoration: none; font-size: 1.1rem; transition: opacity 0.2s; }
    .share-btn.tg { background: #0088cc; }
    .share-btn.wa { background: #25d366; }
    .share-btn:hover { opacity: 0.8; }

    .news-pagination { display: flex; gap: 10px; justify-content: center; margin-top: 40px; }
    .news-page-link { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border: 1px solid #eee; border-radius: 10px; text-decoration: none; color: #555; transition: all 0.3s; font-weight: 600; }
    .news-page-link:hover { border-color: #4facfe; color: #4facfe; }
    .news-page-link.active { background: #4facfe; color: #fff; border-color: #4facfe; box-shadow: 0 4px 10px rgba(79, 172, 254, 0.3); }
</style>
