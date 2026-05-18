<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;
use App\Models\Section;
use App\Models\NewsItem;

Auth::requireAuth();

$sections = Section::all();
$news = NewsItem::all();

$totalViews = array_sum(array_column($news, 'views'));
$totalReactions = array_sum(array_column($news, 'reaction_count'));

// Calculate stats per section
$sectionStats = [];
foreach ($sections as $s) {
    $sNews = array_filter($news, fn($n) => $n['section_id'] === $s['id']);
    $sectionStats[] = [
        'name' => $s['name'],
        'count' => count($sNews),
        'views' => array_sum(array_column($sNews, 'views')),
        'reactions' => array_sum(array_column($sNews, 'reaction_count'))
    ];
}

// Sort by views
usort($sectionStats, fn($a, $b) => $b['views'] <=> $a['views']);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аналитика - NewsManager</title>
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
            <a class="nav-link active" href="analytics.php"><i class="bi bi-bar-chart"></i> Аналитика</a>
            <a class="nav-link" href="backup.php"><i class="bi bi-cloud-arrow-down"></i> Резервное копирование</a>
            <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-5">
            <h2>Аналитика контента</h2>
        </header>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="glass-card text-center">
                    <h6 class="text-muted">Всего просмотров</h6>
                    <h2 class="display-6 fw-bold text-primary"><?php echo number_format($totalViews); ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center">
                    <h6 class="text-muted">Всего реакций</h6>
                    <h2 class="display-6 fw-bold text-danger"><?php echo number_format($totalReactions); ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center">
                    <h6 class="text-muted">Активных разделов</h6>
                    <h2 class="display-6 fw-bold text-success"><?php echo count($sections); ?></h2>
                </div>
            </div>
        </div>

        <div class="glass-card">
            <h5>Статистика по разделам</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Раздел</th>
                            <th>Кол-во публикаций</th>
                            <th>Просмотры</th>
                            <th>Реакции</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sectionStats as $stat): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($stat['name']); ?></strong></td>
                            <td><?php echo $stat['count']; ?></td>
                            <td><i class="bi bi-eye"></i> <?php echo number_format($stat['views']); ?></td>
                            <td><i class="bi bi-heart-fill text-danger"></i> <?php echo number_format($stat['reactions']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
