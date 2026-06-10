<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Models\Section;
use App\Models\NewsItem;

$sectionId = $_GET['id'] ?? '';
if (!$sectionId) exit;

$section = Section::find($sectionId);
if (!$section) exit;

$newsItems = NewsItem::findBySection($sectionId);

header('Content-Type: application/rss+xml; charset=utf-8');

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . "://" . $host . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/api');

echo '<?xml version="1.0" encoding="UTF-8" ?>';
?>
<rss version="2.0">
<channel>
    <title><?php echo htmlspecialchars($section['name']); ?></title>
    <link><?php echo $baseUrl; ?></link>
    <description>Новости раздела <?php echo htmlspecialchars($section['name']); ?></description>
    <language>ru</language>
    <?php foreach ($newsItems as $item):
        $itemUrl = $baseUrl . "/?news_id=" . $item['id']; // This is just a placeholder, in real world it would be the site where shortcode is placed
    ?>
    <item>
        <title><?php echo htmlspecialchars($item['title']); ?></title>
        <link><?php echo $itemUrl; ?></link>
        <description><?php echo htmlspecialchars(mb_substr(strip_tags($item['content']), 0, 300)) . '...'; ?></description>
        <pubDate><?php echo date(DATE_RSS, strtotime($item['publish_at'] ?? $item['created_at'])); ?></pubDate>
        <guid><?php echo $item['id']; ?></guid>
    </item>
    <?php endforeach; ?>
</channel>
</rss>
