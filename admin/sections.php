<?php
require_once __DIR__ . '/../src/autoload.php';
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
        $viewType = $_POST['view_type'] ?? 'cards';
        $itemsPerPage = $_POST['items_per_page'] ?? 10;

        if ($name) {
            Section::save([
                'id' => $id ?: uniqid(),
                'name' => $name,
                'view_type' => $viewType,
                'items_per_page' => (int)$itemsPerPage
            ]);
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
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link active" href="sections.php"><i class="bi bi-folder"></i> Разделы</a>
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Название раздела</label>
                            <input type="text" name="name" class="form-control rounded-3" value="<?php echo htmlspecialchars($editSection['name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Вид отображения</label>
                            <select name="view_type" class="form-select rounded-3">
                                <option value="cards" <?php echo ($editSection['view_type'] ?? '') === 'cards' ? 'selected' : ''; ?>>Карточки</option>
                                <option value="table" <?php echo ($editSection['view_type'] ?? '') === 'table' ? 'selected' : ''; ?>>Таблица</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Новостей на страницу</label>
                            <input type="number" name="items_per_page" class="form-control rounded-3" value="<?php echo $editSection['items_per_page'] ?? 10; ?>" min="1" max="100">
                        </div>
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
                            <th>Вид</th>
                            <th>Страница</th>
                            <th>Shortcode</th>
                            <th class="text-end">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sections as $section): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($section['name']); ?></strong></td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    <i class="bi bi-<?php echo ($section['view_type'] ?? 'cards') === 'cards' ? 'grid-3x3-gap' : 'list-ul'; ?>"></i>
                                    <?php echo ($section['view_type'] ?? 'cards') === 'cards' ? 'Карточки' : 'Таблица'; ?>
                                </span>
                            </td>
                            <td><small class="text-muted"><?php echo $section['items_per_page'] ?? 10; ?></small></td>
                            <td><span class="shortcode-badge" onclick="copyShortcode('<?php echo $section['id']; ?>')">&lt;div data-news-section="<?php echo $section['id']; ?>"&gt;&lt;/div&gt;</span></td>
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
            const baseUrl = '<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . rtrim(str_replace('/admin', '', dirname($_SERVER['SCRIPT_NAME'])), "/"); ?>';
            const text = `<div data-news-section="${id}"></div>\n<script src="${baseUrl}/assets/js/shortcode.js"><\/script>`;
            navigator.clipboard.writeText(text).then(() => {
                alert('Код для вставки скопирован (блок + скрипт)!');
            });
        }
    </script>
</body>
</html>
