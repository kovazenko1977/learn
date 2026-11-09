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
        <button class="btn btn-primary d-lg-none mobile-menu-btn" id="mobileMenuBtn"><i class="bi bi-list"></i></button>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="sidebar" id="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
                <nav class="nav flex-column">
            <a class="nav-link " href="index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link " href="sections.php"><i class="bi bi-folder"></i> Разделы</a>
            <a class="nav-link " href="news.php"><i class="bi bi-newspaper"></i> Новости</a>
            <a class="nav-link " href="media.php"><i class="bi bi-images"></i> Медиа менеджер</a>
            <a class="nav-link " href="analytics.php"><i class="bi bi-bar-chart"></i> Аналитика</a>
            <a class="nav-link " href="subscribers.php"><i class="bi bi-envelope-at"></i> Подписчики</a>
            <a class="nav-link " href="users.php"><i class="bi bi-people"></i> Пользователи</a>
            <a class="nav-link " href="logs.php"><i class="bi bi-list-check"></i> Журнал</a>
            <a class="nav-link " href="health.php"><i class="bi bi-heart-pulse"></i> Состояние</a>
            <a class="nav-link " href="backup.php"><i class="bi bi-cloud-arrow-down"></i> Резервное копирование</a>
            <a class="nav-link " href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <a class="nav-link " href="about.php"><i class="bi bi-info-circle"></i> О программе (40 функций)</a>
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
<script>
    const mBtn = document.getElementById('mobileMenuBtn');
    const sBar = document.getElementById('sidebar');
    const bDrop = document.getElementById('sidebarBackdrop');
    if (mBtn && sBar) {
        mBtn.onclick = () => { sBar.classList.toggle('show'); bDrop && bDrop.classList.toggle('show'); };
        bDrop && (bDrop.onclick = () => { sBar.classList.remove('show'); bDrop.classList.remove('show'); });
    }
</script>
</body>
</html>
