<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;
use App\Models\NewsItem;
use App\Models\Section;

Auth::requireAuth();

$newsItems = NewsItem::all();
$sections = Section::all();

$filterSection = $_GET['filter_section'] ?? '';
$filterStatus = $_GET['filter_status'] ?? '';
$searchQuery = $_GET['q'] ?? '';

if ($filterSection || $filterStatus || $searchQuery) {
    $newsItems = array_filter($newsItems, function($item) use ($filterSection, $filterStatus, $searchQuery) {
        if ($filterSection && $item['section_id'] !== $filterSection) return false;
        if ($filterStatus && ($item['status'] ?? 'published') !== $filterStatus) return false;
        if ($searchQuery) {
            $q = mb_strtolower($searchQuery);
            if (mb_strpos(mb_strtolower($item['title']), $q) === false &&
                mb_strpos(mb_strtolower($item['content']), $q) === false) return false;
        }
        return true;
    });
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=news_export_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

fputcsv($output, ['ID', 'Заголовок', 'Раздел', 'Статус', 'Просмотры', 'Дата создания', 'Дата публикации', 'Бессрочно']);

foreach ($newsItems as $item) {
    $sec = array_filter($sections, fn($s) => $s['id'] === $item['section_id']);
    $secName = !empty($sec) ? reset($sec)['name'] : 'Неизвестно';

    fputcsv($output, [
        $item['id'],
        $item['title'],
        $secName,
        $item['status'] ?? 'published',
        $item['views'] ?? 0,
        $item['created_at'],
        $item['publish_at'] ?? '',
        empty($item['expire_at']) ? 'Да' : 'Нет'
    ]);
}

fclose($output);
exit;
