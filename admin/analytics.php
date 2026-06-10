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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link" href="sections.php"><i class="bi bi-folder"></i> Разделы</a>
            <a class="nav-link" href="news.php"><i class="bi bi-newspaper"></i> Новости</a>
            <a class="nav-link active" href="analytics.php"><i class="bi bi-bar-chart"></i> Аналитика</a>
            <a class="nav-link" href="subscribers.php"><i class="bi bi-envelope-at"></i> Подписчики</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-5">
            <h2>Аналитика контента</h2>
        </header>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="glass-card text-center h-100">
                    <h6 class="text-muted">Всего просмотров</h6>
                    <h2 class="display-6 fw-bold text-primary"><?php echo number_format($totalViews); ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center h-100">
                    <h6 class="text-muted">Всего реакций</h6>
                    <h2 class="display-6 fw-bold text-danger"><?php echo number_format($totalReactions); ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center h-100">
                    <h6 class="text-muted">Активных разделов</h6>
                    <h2 class="display-6 fw-bold text-success"><?php echo count($sections); ?></h2>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <div class="glass-card">
                    <h5>Просмотры по разделам</h5>
                    <canvas id="viewsChart" height="200"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card">
                    <h5>Топ публикаций</h5>
                    <ul class="list-group list-group-flush bg-transparent">
                        <?php
                        $topNews = $news;
                        usort($topNews, fn($a, $b) => ($b['views'] ?? 0) <=> ($a['views'] ?? 0));
                        foreach (array_slice($topNews, 0, 5) as $tn):
                        ?>
                            <li class="list-group-item bg-transparent border-0 px-0 py-2">
                                <div class="d-flex justify-content-between">
                                    <small class="text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($tn['title']); ?></small>
                                    <strong><?php echo $tn['views'] ?? 0; ?></strong>
                                </div>
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar" style="width: <?php echo $totalViews > 0 ? (($tn['views'] ?? 0) / $totalViews * 100) : 0; ?>%"></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="glass-card">
            <h5>Таблица статистики</h5>
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

    <script>
        const ctx = document.getElementById('viewsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($sectionStats, 'name')); ?>,
                datasets: [{
                    label: 'Просмотры',
                    data: <?php echo json_encode(array_column($sectionStats, 'views')); ?>,
                    backgroundColor: 'rgba(79, 172, 254, 0.5)',
                    borderColor: '#4facfe',
                    borderWidth: 1,
                    borderRadius: 10
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
</body>
</html>
