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
            <a class="nav-link" href="backup.php"><i class="bi bi-cloud-arrow-down"></i> Резервное копирование</a>
            <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <a class="nav-link" href="about.php"><i class="bi bi-info-circle"></i> О программе</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-5 d-flex justify-content-between align-items-center">
            <h2>О программе</h2>
        </header>

        <div class="glass-card text-center py-5">
            <h4 class="mb-4"><b>News</b>Manager Pro</h4>
            <p class="lead">Система управления контентом через шорткоды</p>
            <hr class="my-4 mx-auto" style="width: 200px;">
            <div class="mt-4">
                <p class="mb-1">Разработано: <a href="https://wes.by" target="_blank" class="text-decoration-none">wes.by</a></p>
                <p class="mb-1">Контактный телефон: <a href="tel:+375333533971" class="text-decoration-none">+375 33 353-39-71</a></p>
                <p class="mb-0">Разработчик: <b>Коваженко С.Б.</b></p>
            </div>
            <div class="mt-5 text-start mx-auto" style="max-width: 700px;">
                <h5 class="mb-3">Интеграция с WordPress</h5>
                <p class="small text-muted">Для сайтов на WordPress добавьте следующий код в файл <code>functions.php</code> вашей темы:</p>
                <pre class="bg-light p-3 rounded-3 small"><code>add_shortcode('news_section', function($atts) {
    $atts = shortcode_atts(['id' => ''], $atts);
    if (empty($atts['id'])) return '';

    // Автоматическое определение URL панели
    $base_url = '<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]"; ?>';
    $url = $base_url . '/api/shortcode.php?id=' . urlencode($atts['id']);

    $response = wp_remote_get($url);
    if (is_wp_error($response)) return 'Ошибка загрузки';

    return wp_remote_retrieve_body($response);
});</code></pre>
                <p class="small text-muted mt-2">После этого вы сможете использовать шорткод <code>[news_section id="ID_РАЗДЕЛА"]</code> прямо в редакторе записей WordPress.</p>
            </div>

            <div class="mt-5 text-muted small">
                &copy; <?php echo date('Y'); ?> Все права защищены.
            </div>
        </div>
    </div>
</body>
</html>
