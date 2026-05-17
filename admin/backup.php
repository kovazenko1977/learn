<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;

Auth::requireAuth();

$success = '';
if (isset($_POST['create_backup'])) {
    $zip = new ZipArchive();
    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.zip';
    $filepath = __DIR__ . '/../data/backups/' . $filename;

    if (!is_dir(__DIR__ . '/../data/backups/')) {
        mkdir(__DIR__ . '/../data/backups/', 0755, true);
    }

    if ($zip->open($filepath, ZipArchive::CREATE) === TRUE) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../data/'),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(__DIR__ . '/../data/'));

                // Don't include backups in backups
                if (strpos($relativePath, 'backups/') !== 0) {
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
        $zip->close();
        $success = 'Резервная копия создана: ' . $filename;
    }
}

if (isset($_GET['download'])) {
    $file = __DIR__ . '/../data/backups/' . $_GET['download'];
    if (file_exists($file) && strpos(realpath($file), realpath(__DIR__ . '/../data/backups/')) === 0) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename=' . basename($file));
        readfile($file);
        exit;
    }
}

$backupDir = __DIR__ . '/../data/backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}
$backups = array_diff(scandir($backupDir), ['.', '..']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Резервное копирование - NewsManager</title>
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
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link" href="sections.php"><i class="bi bi-folder"></i> Разделы</a>
            <a class="nav-link" href="news.php"><i class="bi bi-newspaper"></i> Новости</a>
            <a class="nav-link active" href="backup.php"><i class="bi bi-cloud-arrow-down"></i> Резервное копирование</a>
            <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-5">
            <h2>Резервное копирование</h2>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="glass-card mb-4 text-center py-5">
            <i class="bi bi-cloud-upload text-primary" style="font-size: 4rem;"></i>
            <h4 class="mt-3">Создать новую копию</h4>
            <p class="text-muted">Все данные (разделы, новости, изображения) будут упакованы в ZIP архив.</p>
            <form method="POST">
                <button type="submit" name="create_backup" class="btn btn-primary rounded-pill px-5 mt-3">Сформировать архив</button>
            </form>
        </div>

        <div class="glass-card">
            <h5>Список доступных копий</h5>
            <div class="table-responsive mt-3">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Имя файла</th>
                            <th>Дата</th>
                            <th class="text-end">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($backups) as $file): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($file); ?></strong></td>
                            <td><?php echo date('d.m.Y H:i', filemtime(__DIR__ . '/../data/backups/' . $file)); ?></td>
                            <td class="text-end">
                                <a href="?download=<?php echo urlencode($file); ?>" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-download"></i> Скачать</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($backups)): ?>
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">Копии еще не создавались</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
