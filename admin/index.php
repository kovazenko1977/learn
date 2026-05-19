<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;
use App\Models\Section;
use App\Models\NewsItem;

Auth::requireAuth();

$sectionsCount = count(Section::all());
$newsCount = count(NewsItem::all());
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - NewsManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
        <nav class="nav flex-column">
            <a class="nav-link active" href="index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link" href="sections.php"><i class="bi bi-folder"></i> Разделы</a>
            <a class="nav-link" href="news.php"><i class="bi bi-newspaper"></i> Новости</a>
            <a class="nav-link" href="media.php"><i class="bi bi-images"></i> Медиа менеджер</a>
            <a class="nav-link" href="analytics.php"><i class="bi bi-bar-chart"></i> Аналитика</a>
            <a class="nav-link" href="subscribers.php"><i class="bi bi-envelope-at"></i> Подписчики</a>
            <a class="nav-link" href="users.php"><i class="bi bi-people"></i> Пользователи</a>
            <a class="nav-link" href="logs.php"><i class="bi bi-list-check"></i> Журнал</a>
            <a class="nav-link" href="health.php"><i class="bi bi-heart-pulse"></i> Состояние</a>
            <a class="nav-link" href="backup.php"><i class="bi bi-cloud-arrow-down"></i> Резервное копирование</a>
            <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-5 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <h2 class="mb-0">Дашборд</h2>
                <form action="search.php" method="GET" class="d-none d-md-flex">
                    <input type="text" name="q" class="form-control rounded-pill px-3 py-1" placeholder="Поиск по системе..." style="width: 250px;">
                </form>
            </div>
            <div class="user-info d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary rounded-circle" id="themeToggle"><i class="bi bi-moon"></i></button>
                <span class="badge bg-primary px-3 py-2"><?php echo ($_SESSION['role'] ?? '') === 'admin' ? 'Администратор' : 'Редактор'; ?>: <?php echo $_SESSION['username'] ?? 'System'; ?></span>
            </div>
        </header>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="glass-card text-center">
                    <i class="bi bi-folder stat-icon" style="font-size: 2.5rem; color: #4facfe; margin-bottom: 1rem; display: block;"></i>
                    <div class="stat-value" style="font-size: 2rem; font-weight: 700;"><?php echo $sectionsCount; ?></div>
                    <div class="stat-label" style="color: #777; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px;">Разделов создано</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center">
                    <i class="bi bi-newspaper stat-icon" style="font-size: 2.5rem; color: #4facfe; margin-bottom: 1rem; display: block;"></i>
                    <div class="stat-value" style="font-size: 2rem; font-weight: 700;"><?php echo $newsCount; ?></div>
                    <div class="stat-label" style="color: #777; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px;">Новостей/Материалов</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center">
                    <i class="bi bi-clock stat-icon" style="font-size: 2.5rem; color: #4facfe; margin-bottom: 1rem; display: block;"></i>
                    <div class="stat-value" style="font-size: 2rem; font-weight: 700;"><?php echo date('H:i'); ?></div>
                    <div class="stat-label" style="color: #777; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px;">Системное время</div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12">
                <div class="glass-card">
                    <h5>Быстрые действия</h5>
                    <div class="d-flex gap-3 mt-3">
                        <a href="sections.php?action=add" class="btn btn-outline-primary rounded-pill">Создать раздел</a>
                        <a href="news.php?action=add" class="btn btn-outline-primary rounded-pill">Добавить новость</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const themeToggle = document.getElementById('themeToggle');
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
        themeToggle.innerHTML = currentTheme === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';

        themeToggle.onclick = () => {
            const theme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            themeToggle.innerHTML = theme === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
        };
    </script>
</body>
</html>
