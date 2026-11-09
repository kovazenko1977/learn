<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Models\NewsItem;

header('Content-Type: application/xml; charset=utf-8');

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . "://" . $host . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/api');

$newsItems = NewsItem::all();

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?php echo $baseUrl; ?>/</loc>
        <priority>1.0</priority>
    </url>
    <?php foreach ($newsItems as $item):
        if (($item['status'] ?? 'published') !== 'published') continue;
    ?>
    <url>
        <loc><?php echo $baseUrl; ?>/?news_id=<?php echo $item['id']; ?></loc>
        <lastmod><?php echo date('Y-m-d', strtotime($item['updated_at'] ?? $item['created_at'])); ?></lastmod>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>
</urlset>
