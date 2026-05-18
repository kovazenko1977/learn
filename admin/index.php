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
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.3);
            --accent-color: #4facfe;
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #333;
        }
        .sidebar {
            width: 280px;
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border-right: 1px solid var(--glass-border);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 2rem 1rem;
            z-index: 1000;
        }
        .main-content {
            margin-left: 280px;
            padding: 2rem;
        }
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        .glass-card:hover {
            transform: translateY(-5px);
        }
        .nav-link {
            color: #555;
            padding: 0.8rem 1rem;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(79, 172, 254, 0.15);
            color: var(--accent-color);
        }
        .nav-link i {
            margin-right: 12px;
            font-size: 1.2rem;
        }
        .stat-icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 1rem;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
        }
        .stat-label {
            color: #777;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
        <nav class="nav flex-column">
            <a class="nav-link active" href="index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link" href="sections.php"><i class="bi bi-folder"></i> Разделы</a>
            <a class="nav-link" href="news.php"><i class="bi bi-newspaper"></i> Новости</a>
            <a class="nav-link" href="analytics.php"><i class="bi bi-bar-chart"></i> Аналитика</a>
            <a class="nav-link" href="backup.php"><i class="bi bi-cloud-arrow-down"></i> Резервное копирование</a>
            <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <a class="nav-link" href="about.php"><i class="bi bi-info-circle"></i> О программе</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-5 d-flex justify-content-between align-items-center">
            <h2>Дашборд</h2>
            <div class="user-info">
                <span class="badge bg-primary px-3 py-2">Администратор</span>
            </div>
        </header>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="glass-card text-center">
                    <i class="bi bi-folder stat-icon"></i>
                    <div class="stat-value"><?php echo $sectionsCount; ?></div>
                    <div class="stat-label">Разделов создано</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center">
                    <i class="bi bi-newspaper stat-icon"></i>
                    <div class="stat-value"><?php echo $newsCount; ?></div>
                    <div class="stat-label">Новостей/Материалов</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center">
                    <i class="bi bi-clock stat-icon"></i>
                    <div class="stat-value"><?php echo date('H:i'); ?></div>
                    <div class="stat-label">Системное время</div>
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
</body>
</html>
