<?php
require_once __DIR__ . '/../../src/autoload.php';
use App\Helpers\Auth;
use App\Models\Section;

Auth::requireAuth();

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_section'])) {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';

        if ($name) {
            Section::save(['id' => $id ?: uniqid(), 'name' => $name]);
            $success = 'Раздел успешно сохранен';
            $action = 'list';
        } else {
            $error = 'Введите название раздела';
        }
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    Section::delete($_GET['id']);
    $success = 'Раздел удален';
    $action = 'list';
}

$sections = Section::all();
$editSection = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editSection = Section::find($_GET['id']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление разделами - NewsManager</title>
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
        .shortcode-badge {
            background: rgba(79, 172, 254, 0.1);
            color: var(--accent-color);
            padding: 5px 12px;
            border-radius: 8px;
            font-family: monospace;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="/admin/index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link active" href="/admin/sections.php"><i class="bi bi-folder"></i> Разделы</a>
            <a class="nav-link" href="/admin/news.php"><i class="bi bi-newspaper"></i> Новости</a>
            <a class="nav-link" href="/admin/backup.php"><i class="bi bi-cloud-arrow-down"></i> Резервное копирование</a>
            <a class="nav-link" href="/admin/settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <hr>
            <a class="nav-link text-danger" href="/logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-5 d-flex justify-content-between align-items-center">
            <h2>Управление разделами</h2>
            <a href="?action=add" class="btn btn-primary rounded-pill px-4">Добавить раздел</a>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="glass-card mb-4">
                <h5><?php echo $action === 'add' ? 'Новый раздел' : 'Редактировать раздел'; ?></h5>
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $editSection['id'] ?? ''; ?>">
                    <div class="mb-3">
                        <label class="form-label">Название раздела</label>
                        <input type="text" name="name" class="form-control rounded-3" value="<?php echo htmlspecialchars($editSection['name'] ?? ''); ?>" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="save_section" class="btn btn-primary rounded-pill px-4">Сохранить</button>
                        <a href="sections.php" class="btn btn-light rounded-pill px-4">Отмена</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="glass-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Shortcode</th>
                            <th class="text-end">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sections as $section): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($section['name']); ?></strong></td>
                            <td><span class="shortcode-badge" onclick="copyShortcode('<?php echo $section['id']; ?>')">[news-section id="<?php echo $section['id']; ?>"]</span></td>
                            <td class="text-end">
                                <a href="?action=edit&id=<?php echo $section['id']; ?>" class="btn btn-sm btn-outline-secondary rounded-pill me-1"><i class="bi bi-pencil"></i></a>
                                <a href="?action=delete&id=<?php echo $section['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Вы уверены?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($sections)): ?>
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">Разделы еще не созданы</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyShortcode(id) {
            const text = `[news-section id="${id}"]`;
            navigator.clipboard.writeText(text).then(() => {
                alert('Шорткод скопирован: ' + text);
            });
        }
    </script>
</body>
</html>
