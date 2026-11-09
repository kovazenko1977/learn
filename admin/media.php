<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;

Auth::requireAuth();

$uploadDir = __DIR__ . '/../data/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$success = '';
$error = '';

// Handle direct file upload from media manager
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_upload'])) {
    $files = $_FILES['media_upload'];
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm', 'ogv', 'ogg', 'mov'];
    $uploadedCount = 0;

    $fileNames = is_array($files['name']) ? $files['name'] : [$files['name']];
    $fileTmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
    $fileErrors = is_array($files['error']) ? $files['error'] : [$files['error']];

    foreach ($fileNames as $i => $origName) {
        if ($fileErrors[$i] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExts)) {
                $isVideo = in_array($ext, ['mp4', 'webm', 'ogv', 'ogg', 'mov']);
                $prefix = $isVideo ? 'vid_' : 'img_';
                $destName = uniqid($prefix) . '.' . $ext;
                if (move_uploaded_file($fileTmpNames[$i], $uploadDir . $destName)) {
                    $uploadedCount++;
                    Auth::log("Медиа менеджер: загружен файл $destName");
                }
            }
        }
    }
    if ($uploadedCount > 0) {
        $success = "Успешно загружено файлов: $uploadedCount";
    } else {
        $error = "Не удалось загрузить файлы (проверьте формат и размер)";
    }
}

if (isset($_GET['delete'])) {
    $filename = basename($_GET['delete']);
    $filepath = $uploadDir . $filename;
    if (file_exists($filepath)) {
        unlink($filepath);
        Auth::log("Удален медиафайл: $filename");
        $success = "Файл успешно удален";
    }
}

$allFiles = array_diff(scandir($uploadDir), ['.', '..']);
natsort($allFiles);
$allFiles = array_reverse($allFiles);

// Filter by type: all, image, video
$filterType = $_GET['type'] ?? 'all';
$files = array_filter($allFiles, function($f) use ($filterType) {
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    $isVideo = in_array($ext, ['mp4', 'webm', 'ogv', 'ogg', 'mov']);
    if ($filterType === 'image') return !$isVideo;
    if ($filterType === 'video') return $isVideo;
    return true;
});
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
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.5rem;
        }
        .media-item {
            position: relative;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.08);
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }
        .media-item img, .media-item video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .media-type-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background: rgba(0,0,0,0.7);
            color: #fff;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            z-index: 2;
        }
        .media-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.65);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 5;
        }
        .media-item:hover .media-overlay {
            opacity: 1;
        }
        .file-info {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(255,255,255,0.95);
            padding: 6px 8px;
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            z-index: 3;
            color: #333;
        }
        [data-theme="dark"] .file-info {
            background: rgba(30,30,30,0.95);
            color: #f0f0f0;
        }
        .upload-dropzone {
            border: 2px dashed #4facfe;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            background: rgba(79, 172, 254, 0.05);
            cursor: pointer;
            transition: background 0.2s;
        }
        .upload-dropzone:hover {
            background: rgba(79, 172, 254, 0.1);
        }
    </style>
</head>
<body>
    <button class="btn btn-primary d-lg-none mobile-menu-btn" id="mobileMenuBtn">
        <i class="bi bi-list"></i>
    </button>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="sidebar" id="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link" href="sections.php"><i class="bi bi-folder"></i> Разделы</a>
            <a class="nav-link" href="news.php"><i class="bi bi-newspaper"></i> Новости</a>
            <a class="nav-link active" href="media.php"><i class="bi bi-images"></i> Медиа менеджер</a>
            <a class="nav-link" href="analytics.php"><i class="bi bi-bar-chart"></i> Аналитика</a>
            <a class="nav-link" href="subscribers.php"><i class="bi bi-envelope-at"></i> Подписчики</a>
            <a class="nav-link" href="users.php"><i class="bi bi-people"></i> Пользователи</a>
            <a class="nav-link" href="logs.php"><i class="bi bi-list-check"></i> Журнал</a>
            <a class="nav-link" href="health.php"><i class="bi bi-heart-pulse"></i> Состояние</a>
            <a class="nav-link" href="backup.php"><i class="bi bi-cloud-arrow-down"></i> Резервное копирование</a>
            <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <a class="nav-link" href="about.php"><i class="bi bi-info-circle"></i> О программе (40 функций)</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="mb-1">Медиа менеджер</h2>
                <p class="text-muted mb-0 small">Хранилище фото и видеоматериалов с прямыми ссылками для вставок в контент и Elementor</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary rounded-circle" id="themeToggle"><i class="bi bi-moon"></i></button>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4"><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Upload Box -->
        <div class="glass-card mb-4">
            <h5 class="mb-3"><i class="bi bi-cloud-arrow-up-fill text-primary"></i> Загрузить новые фото или видео</h5>
            <form method="POST" enctype="multipart/form-data" class="d-flex flex-column flex-md-row gap-3 align-items-center">
                <input type="file" name="media_upload[]" multiple class="form-control rounded-3" accept="image/*,video/mp4,video/webm,video/ogg">
                <button type="submit" class="btn btn-primary rounded-pill px-4 text-nowrap"><i class="bi bi-upload"></i> Загрузить файлы</button>
            </form>
            <small class="text-muted mt-2 d-block">Поддерживаются форматы: JPG, PNG, WEBP, GIF, SVG, видео MP4 и WebM. Файлы сохраняются в защищенном хранилище.</small>
        </div>

        <!-- Filter tabs -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="btn-group rounded-pill" role="group">
                <a href="media.php?type=all" class="btn btn-sm <?php echo $filterType === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-start-pill px-3">Все (<?php echo count($allFiles); ?>)</a>
                <a href="media.php?type=image" class="btn btn-sm <?php echo $filterType === 'image' ? 'btn-primary' : 'btn-outline-primary'; ?> px-3">Только фото</a>
                <a href="media.php?type=video" class="btn btn-sm <?php echo $filterType === 'video' ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-end-pill px-3">Только видео</a>
            </div>
        </div>

        <div class="glass-card">
            <div class="media-grid">
                <?php foreach ($files as $file):
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $isVideo = in_array($ext, ['mp4', 'webm', 'ogv', 'ogg', 'mov']);
                    $url = "../api/image.php?name=" . urlencode($file);
                ?>
                <div class="media-item">
                    <span class="media-type-badge"><i class="bi <?php echo $isVideo ? 'bi-camera-video' : 'bi-image'; ?>"></i> <?php echo strtoupper($ext); ?></span>
                    <?php if ($isVideo): ?>
                        <video src="<?php echo $url; ?>" preload="metadata" muted></video>
                    <?php else: ?>
                        <img src="<?php echo $url; ?>" alt="" loading="lazy">
                    <?php endif; ?>
                    <div class="media-overlay">
                        <button class="btn btn-sm btn-light rounded-pill" onclick="copyUrl('<?php echo $url; ?>')" title="Скопировать прямую ссылку"><i class="bi bi-link-45deg"></i></button>
                        <a href="<?php echo $url; ?>" target="_blank" class="btn btn-sm btn-light rounded-pill" title="Открыть в новом окне"><i class="bi bi-eye"></i></a>
                        <a href="?delete=<?php echo urlencode($file); ?>&type=<?php echo $filterType; ?>" class="btn btn-sm btn-danger rounded-pill" onclick="return confirm('Удалить файл?')" title="Удалить"><i class="bi bi-trash"></i></a>
                    </div>
                    <div class="file-info" title="<?php echo htmlspecialchars($file); ?>"><?php echo htmlspecialchars($file); ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($files)): ?>
                    <div class="col-12 py-5 text-center text-muted">
                        <i class="bi bi-folder-x" style="font-size: 3rem;"></i>
                        <p class="mt-2">Медиафайлы не найдены. Воспользуйтесь формой выше для загрузки изображений или видео.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function copyUrl(url) {
            const absoluteUrl = window.location.origin + window.location.pathname.replace('admin/media.php', '') + url.replace('../', '');
            navigator.clipboard.writeText(absoluteUrl).then(() => {
                alert('URL скопирован в буфер обмена: ' + absoluteUrl);
            });
        }

        // Dark theme toggle
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            const currentTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', currentTheme);
            themeToggle.innerHTML = currentTheme === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
            themeToggle.onclick = () => {
                const theme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                themeToggle.innerHTML = theme === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
            };
        }

        // Mobile menu toggle
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (mobileBtn && sidebar) {
            mobileBtn.onclick = () => {
                sidebar.classList.toggle('show');
                backdrop.classList.toggle('show');
            };
            backdrop.onclick = () => {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
            };
        }
    </script>
</body>
</html>
