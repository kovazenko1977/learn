<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;
use App\Database\JsonStore;
use App\Models\Section;

Auth::requireAuth();

$store = new JsonStore(__DIR__ . '/../data/subscribers.json');
$subs = $store->getAll();
$sections = Section::all();

if (isset($_GET['delete'])) {
    $email = $_GET['delete'];
    $secId = $_GET['sec'];
    $subs = array_filter($subs, fn($s) => !($s['email'] === $email && $s['section_id'] === $secId));
    $store->set(array_values($subs));
    Auth::log("Удален подписчик: $email");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подписчики - NewsManager</title>
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
            <a class="nav-link active" href="subscribers.php"><i class="bi bi-envelope-at"></i> Подписчики</a>
            <a class="nav-link" href="media.php"><i class="bi bi-images"></i> Медиа</a>
            <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-5 d-flex justify-content-between align-items-center">
            <h2>Подписчики</h2>
            <a href="export_subs.php" class="btn btn-outline-secondary rounded-pill px-4">Экспорт CSV</a>
        </header>

        <div class="glass-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Раздел</th>
                            <th>Дата подписки</th>
                            <th class="text-end">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subs as $s):
                            $sec = array_filter($sections, fn($sec) => $sec['id'] === $s['section_id']);
                            $secName = !empty($sec) ? reset($sec)['name'] : 'Неизвестно';
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($s['email']); ?></strong></td>
                            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($secName); ?></span></td>
                            <td><small class="text-muted"><?php echo $s['date']; ?></small></td>
                            <td class="text-end">
                                <a href="?delete=<?php echo urlencode($s['email']); ?>&sec=<?php echo $s['section_id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Удалить подписчика?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($subs)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">Подписчиков пока нет</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
