<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;

Auth::requireAuth();

$uploadDir = __DIR__ . '/../data/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$success = '';
$error = '';

if (isset($_GET['delete'])) {
    $filename = basename($_GET['delete']);
    $filepath = $uploadDir . $filename;
    if (file_exists($filepath)) {
        unlink($filepath);
        Auth::log("Удален файл: $filename");
        $success = "Файл удален";
    }
}

$files = array_diff(scandir($uploadDir), ['.', '..']);
natsort($files);
$files = array_reverse($files);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Медиа менеджер - NewsManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1.5rem;
        }
        .media-item {
            position: relative;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid #eee;
            aspect-ratio: 1;
        }
        .media-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .media-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .media-item:hover .media-overlay {
            opacity: 1;
        }
        .file-info {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(255,255,255,0.9);
            padding: 5px;
            font-size: 0.7rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link" href="sections.php"><i class="bi bi-folder"></i> Разделы</a>
            <a class="nav-link" href="news.php"><i class="bi bi-newspaper"></i> Новости</a>
            <a class="nav-link active" href="media.php"><i class="bi bi-images"></i> Медиа менеджер</a>
            <a class="nav-link" href="analytics.php"><i class="bi bi-bar-chart"></i> Аналитика</a>
            <a class="nav-link" href="users.php"><i class="bi bi-people"></i> Пользователи</a>
            <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-5">
            <h2>Медиа менеджер</h2>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4"><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="glass-card">
            <div class="media-grid">
                <?php foreach ($files as $file):
                    $url = "../api/image.php?name=" . $file;
                ?>
                <div class="media-item">
                    <img src="<?php echo $url; ?>" alt="">
                    <div class="media-overlay">
                        <button class="btn btn-sm btn-light rounded-pill" onclick="copyUrl('<?php echo $url; ?>')"><i class="bi bi-link"></i></button>
                        <a href="?delete=<?php echo urlencode($file); ?>" class="btn btn-sm btn-danger rounded-pill" onclick="return confirm('Удалить файл?')"><i class="bi bi-trash"></i></a>
                    </div>
                    <div class="file-info"><?php echo htmlspecialchars($file); ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($files)): ?>
                    <p class="text-muted">Файлов пока нет</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function copyUrl(url) {
            const absoluteUrl = window.location.origin + window.location.pathname.replace('admin/media.php', '') + url.replace('../', '');
            navigator.clipboard.writeText(absoluteUrl).then(() => {
                alert('URL скопирован!');
            });
        }
    </script>
</body>
</html>
