<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;
use App\Models\Section;
use App\Models\NewsItem;

Auth::requireAuth();

$q = trim($_GET['q'] ?? '');
$results = [];

if ($q) {
    $newsItems = NewsItem::all();
    $sections = Section::all();

    foreach ($newsItems as $item) {
        if (mb_stripos($item['title'], $q) !== false || mb_stripos(strip_tags($item['content']), $q) !== false) {
            $sec = array_filter($sections, fn($s) => $s['id'] === $item['section_id']);
            $item['section_name'] = !empty($sec) ? reset($sec)['name'] : 'Неизвестно';
            $results[] = [
                'type' => 'news',
                'title' => $item['title'],
                'url' => "news.php?action=edit&id=" . $item['id'],
                'meta' => $item['section_name']
            ];
        }
    }

    foreach ($sections as $sec) {
        if (mb_stripos($sec['name'], $q) !== false) {
            $results[] = [
                'type' => 'section',
                'title' => $sec['name'],
                'url' => "sections.php?action=edit&id=" . $sec['id'],
                'meta' => 'Раздел'
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск - NewsManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link" href="sections.php"><i class="bi bi-folder"></i> Разделы</a>
            <a class="nav-link" href="news.php"><i class="bi bi-newspaper"></i> Новости</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-5">
            <h2>Поиск: <?php echo htmlspecialchars($q); ?></h2>
            <p class="text-muted">Найдено результатов: <?php echo count($results); ?></p>
        </header>

        <div class="glass-card">
            <div class="list-group list-group-flush">
                <?php foreach ($results as $res): ?>
                <a href="<?php echo $res['url']; ?>" class="list-group-item list-group-item-action py-3">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1"><?php echo htmlspecialchars($res['title']); ?></h6>
                        <small class="text-muted"><?php echo $res['meta']; ?></small>
                    </div>
                    <small class="text-primary"><?php echo $res['type'] === 'news' ? 'Редактировать новость' : 'Настройки раздела'; ?></small>
                </a>
                <?php endforeach; ?>
                <?php if ($q && empty($results)): ?>
                    <p class="text-center py-5 text-muted">Ничего не найдено</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
