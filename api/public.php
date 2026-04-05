<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/javascript; charset=utf-8');

$news_file = __DIR__ . '/../data/news.json';
$news = [];

if (file_exists($news_file)) {
    $news = json_decode(file_get_contents($news_file), true);
    // Only published news
    $news = array_filter($news, function($item) {
        return ($item['status'] ?? '') === 'published';
    });
    // Sort by date
    usort($news, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}

// Robustly calculate the base URL for images
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];
$script_path = $_SERVER['SCRIPT_NAME']; // e.g., /news/api/public.php
$base_dir = str_replace('api/public.php', '', $script_path); // e.g., /news/
$baseUrl = $protocol . "://" . $host . $base_dir;

$html = '<div class="wes-news-container">';
foreach ($news as $item) {
    $date = date('d.m.Y H:i', strtotime($item['date']));
    $img = $item['image'] ? '<img src="' . $baseUrl . $item['image'] . '" class="wes-news-img">' : '';
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
.wes-news-container { font-family: sans-serif; max-width: 800px; margin: 0 auto; }
.wes-news-item { display: flex; gap: 20px; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
.wes-news-img { width: 200px; height: 150px; object-fit: cover; border-radius: 8px; }
.wes-news-content { flex: 1; }
.wes-news-date { font-size: 0.8rem; color: #888; margin-bottom: 5px; }
.wes-news-title { margin: 0 0 10px 0; font-size: 1.4rem; color: #333; }
.wes-news-text { line-height: 1.6; color: #555; }
@media (max-width: 600px) {
    .wes-news-item { flex-direction: column; }
    .wes-news-img { width: 100%; height: auto; }
}';

// Escape for JS
$html_escaped = json_encode($html);
$css_escaped = json_encode('<style>' . $css . '</style>');

echo "
(function() {
    const container = document.getElementById('news-feed');
    if (container) {
        container.innerHTML = $css_escaped + $html_escaped;
    } else {
        console.error('Container #news-feed not found');
    }
})();";
