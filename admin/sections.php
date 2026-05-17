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

        if ($name) {
            $data = [
                'id' => $id ?: uniqid(),
                'name' => $name,
                'view_type' => $_POST['view_type'] ?? 'cards',
                'items_per_page' => (int)($_POST['items_per_page'] ?? 10),
                'sort_by' => $_POST['sort_by'] ?? 'date_desc',
                'custom_css' => $_POST['custom_css'] ?? '',
                'lang_read_more' => $_POST['lang_read_more'] ?? 'Читать далее',
                'lang_search_placeholder' => $_POST['lang_search_placeholder'] ?? 'Поиск новостей...',
                'show_date' => isset($_POST['show_date']),
                'show_title' => isset($_POST['show_title']),
                'show_views' => isset($_POST['show_views']),
                'show_author' => isset($_POST['show_author']),
                'show_reading_time' => isset($_POST['show_reading_time']),
                'show_tags' => isset($_POST['show_tags']),
                'show_search' => isset($_POST['show_search']),
                'show_reactions' => isset($_POST['show_reactions']),
                'show_share' => isset($_POST['show_share']),
            ];

            Section::save($data);
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

        <?php if ($action === 'add' || $action === 'edit'):
            $s = $editSection ?: Section::getDefaults();
        ?>
            <div class="glass-card mb-4">
                <h5><?php echo $action === 'add' ? 'Новый раздел' : 'Редактировать раздел'; ?></h5>
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $s['id'] ?? ''; ?>">

                    <ul class="nav nav-tabs mb-4" id="sectionTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">Основные</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="display-tab" data-bs-toggle="tab" data-bs-target="#display" type="button">Отображение</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="lang-tab" data-bs-toggle="tab" data-bs-target="#lang" type="button">Локализация</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="css-tab" data-bs-toggle="tab" data-bs-target="#css" type="button">Custom CSS</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="sectionTabsContent">
                        <!-- Основные -->
                        <div class="tab-pane fade show active" id="general">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Название раздела</label>
                                    <input type="text" name="name" class="form-control rounded-3" value="<?php echo htmlspecialchars($s['name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Вид отображения</label>
                                    <select name="view_type" class="form-select rounded-3">
                                        <option value="cards" <?php echo ($s['view_type'] ?? '') === 'cards' ? 'selected' : ''; ?>>Карточки</option>
                                        <option value="table" <?php echo ($s['view_type'] ?? '') === 'table' ? 'selected' : ''; ?>>Таблица</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Сортировка</label>
                                    <select name="sort_by" class="form-select rounded-3">
                                        <option value="date_desc" <?php echo ($s['sort_by'] ?? '') === 'date_desc' ? 'selected' : ''; ?>>Новые сверху</option>
                                        <option value="date_asc" <?php echo ($s['sort_by'] ?? '') === 'date_asc' ? 'selected' : ''; ?>>Старые сверху</option>
                                        <option value="views_desc" <?php echo ($s['sort_by'] ?? '') === 'views_desc' ? 'selected' : ''; ?>>По просмотрам</option>
                                        <option value="reactions_desc" <?php echo ($s['sort_by'] ?? '') === 'reactions_desc' ? 'selected' : ''; ?>>По популярности</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Новостей на страницу</label>
                                    <input type="number" name="items_per_page" class="form-control rounded-3" value="<?php echo $s['items_per_page'] ?? 10; ?>" min="1" max="100">
                                </div>
                            </div>
                        </div>

                        <!-- Отображение -->
                        <div class="tab-pane fade" id="display">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="show_title" <?php echo ($s['show_title'] ?? true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Заголовок</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="show_date" <?php echo ($s['show_date'] ?? true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Дата</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="show_views" <?php echo ($s['show_views'] ?? true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Просмотры</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="show_author" <?php echo ($s['show_author'] ?? false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Автор</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="show_reading_time" <?php echo ($s['show_reading_time'] ?? true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Время чтения</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="show_tags" <?php echo ($s['show_tags'] ?? true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Теги</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="show_search" <?php echo ($s['show_search'] ?? true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Поиск</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="show_reactions" <?php echo ($s['show_reactions'] ?? true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Реакции (Лайки)</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="show_share" <?php echo ($s['show_share'] ?? true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Кнопки соцсетей</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Локализация -->
                        <div class="tab-pane fade" id="lang">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Текст "Читать далее"</label>
                                    <input type="text" name="lang_read_more" class="form-control rounded-3" value="<?php echo htmlspecialchars($s['lang_read_more'] ?? 'Читать далее'); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Placeholder поиска</label>
                                    <input type="text" name="lang_search_placeholder" class="form-control rounded-3" value="<?php echo htmlspecialchars($s['lang_search_placeholder'] ?? 'Поиск новостей...'); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- CSS -->
                        <div class="tab-pane fade" id="css">
                            <div class="mb-3">
                                <label class="form-label">Собственные CSS стили</label>
                                <textarea name="custom_css" class="form-control rounded-3 font-monospace" rows="6"><?php echo htmlspecialchars($s['custom_css'] ?? ''); ?></textarea>
                                <small class="text-muted">Эти стили будут применены только для этого раздела.</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" name="save_section" class="btn btn-primary rounded-pill px-4">Сохранить раздел</button>
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
