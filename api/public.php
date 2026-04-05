<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$news_file = __DIR__ . '/../data/news.json';
$news = [];
$requested_group = $_GET['group'] ?? 'default';

if (file_exists($news_file)) {
    $news = json_decode(file_get_contents($news_file), true);

    $now = time();

    // Only published news AND where date is NOT in the future AND group matches
    $news = array_filter($news, function($item) use ($now, $requested_group) {
        $is_published = ($item['status'] ?? '') === 'published';
        $is_not_future = strtotime($item['date']) <= $now;
        $group_match = ($item['group_id'] ?? 'default') === $requested_group;
        return $is_published && $is_not_future && $group_match;
    });

    // Sort by date descending (Newest first)
    usort($news, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}

// Robustly calculate the base URL for images
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];
$script_path = $_SERVER['SCRIPT_NAME'];
$base_dir = str_replace('api/public.php', '', $script_path);
$baseUrl = $protocol . "://" . $host . $base_dir;

$html = '<div class="wes-news-container">';
foreach ($news as $item) {
    $date = date('d.m.Y H:i', strtotime($item['date']));
    $img_width = $item['image_width'] ?? '100%';
    $img = $item['image'] ? '<img src="' . $baseUrl . $item['image'] . '" class="wes-news-img" style="width: ' . htmlspecialchars($img_width) . ';">' : '';
    $html .= '
    <div class="wes-news-item">
        ' . $img . '
        <div class="wes-news-content">
            <div class="wes-news-date">' . $date . '</div>
            <h3 class="wes-news-title">' . htmlspecialchars($item['title']) . '</h3>
            <div class="wes-news-text">' . $item['content'] . '</div>
        </div>
    </div>';
}
if (empty($news)) {
    $html .= '<p>Новостей пока нет.</p>';
}
$html .= '</div>';

$css = '
.wes-news-container { font-family: sans-serif; width: 100%; margin: 0; padding: 0; }
.wes-news-item { display: block; width: 100%; margin-bottom: 50px; border-bottom: 1px solid #eee; padding-bottom: 30px; }
.wes-news-img { width: 100%; height: auto; display: block; border-radius: 8px; margin-bottom: 20px; }
.wes-news-content { width: 100%; }
.wes-news-date { font-size: 0.9rem; color: #888; margin-bottom: 10px; }
.wes-news-title { margin: 0 0 15px 0; font-size: 1.8rem; color: #333; line-height: 1.3; }
.wes-news-text { line-height: 1.8; color: #444; font-size: 1.1rem; }
.wes-news-text img { max-width: 100%; height: auto; }
';

// Escape for JS
$html_escaped = json_encode($html);
$css_escaped = json_encode('<style>' . $css . '</style>');

echo "
(function() {
    const script = document.currentScript;
    const containerId = script && script.getAttribute('data-container') ? script.getAttribute('data-container') : 'news-feed';
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = $css_escaped + $html_escaped;
    } else {
        console.error('News Feed Container not found: ' + containerId);
    }
})();";
