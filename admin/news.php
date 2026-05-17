<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;
use App\Models\Section;
use App\Models\NewsItem;

Auth::requireAuth();

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle Bulk Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $selectedIds = $_POST['selected_ids'] ?? [];
    if (!empty($selectedIds)) {
        if ($_POST['bulk_action'] === 'delete') {
            foreach ($selectedIds as $id) {
                NewsItem::delete($id);
            }
            $success = 'Выбранные новости удалены';
        } elseif ($_POST['bulk_action'] === 'publish') {
            foreach ($selectedIds as $id) {
                $item = NewsItem::find($id);
                if ($item) {
                    $item['status'] = 'published';
                    NewsItem::save($item);
                }
            }
            $success = 'Выбранные новости опубликованы';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_news'])) {
        $id = $_POST['id'] ?? null;

        $thumbnail = $_POST['old_thumbnail'] ?? '';
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../data/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('thumb_') . '.' . $ext;
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadDir . $filename)) {
                $thumbnail = 'api/image.php?name=' . $filename;
            }
        }

        $data = [
            'id' => $id ?: uniqid(),
            'section_id' => $_POST['section_id'] ?? '',
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'thumbnail' => $thumbnail,
            'status' => $_POST['status'] ?? 'published',
            'is_pinned' => isset($_POST['is_pinned']),
            'publish_at' => ($_POST['publish_at'] ?? '') ?: null,
            'expire_at' => ($_POST['expire_at'] ?? '') ?: null,
            'seo_title' => $_POST['seo_title'] ?? '',
            'seo_description' => $_POST['seo_description'] ?? '',
            'tags' => array_filter(array_map('trim', explode(',', $_POST['tags'] ?? ''))),
            'author' => $_POST['author'] ?? '',
            'updated_at' => date('Y-m-d H:i:s'),
            'views' => (int)($_POST['views'] ?? 0),
            'reaction_count' => (int)($_POST['reaction_count'] ?? 0)
        ];

        if ($data['title'] && $data['section_id']) {
            NewsItem::save($data);
            $success = 'Новость успешно сохранена';
            $action = 'list';
        } else {
            $error = 'Заполните заголовок и выберите раздел';
        }
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    NewsItem::delete($_GET['id']);
    $success = 'Новость удалена';
    $action = 'list';
}

$newsItems = NewsItem::all();

// Filter and Search
$filterSection = $_GET['filter_section'] ?? '';
$filterStatus = $_GET['filter_status'] ?? '';
$searchQuery = $_GET['q'] ?? '';

if ($filterSection || $filterStatus || $searchQuery) {
    $newsItems = array_filter($newsItems, function($item) use ($filterSection, $filterStatus, $searchQuery) {
        if ($filterSection && $item['section_id'] !== $filterSection) return false;
        if ($filterStatus && ($item['status'] ?? 'published') !== $filterStatus) return false;
        if ($searchQuery) {
            $q = mb_strtolower($searchQuery);
            if (mb_strpos(mb_strtolower($item['title']), $q) === false &&
                mb_strpos(mb_strtolower($item['content']), $q) === false) return false;
        }
        return true;
    });
}

// Sorting
usort($newsItems, function($a, $b) {
    return ($b['publish_at'] ?? $b['created_at']) <=> ($a['publish_at'] ?? $a['created_at']);
});

$sections = Section::all();
$editItem = null;
if (($action === 'edit' || $action === 'add') && isset($_GET['id'])) {
    $editItem = NewsItem::find($_GET['id']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление новостями - NewsManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .ql-toolbar {
            background: #f8f9fa;
            border-radius: 10px 10px 0 0;
        }
        .preview-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            border: 1px solid #eee;
            min-height: 200px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link" href="sections.php"><i class="bi bi-folder"></i> Разделы</a>
            <a class="nav-link active" href="news.php"><i class="bi bi-newspaper"></i> Новости</a>
            <a class="nav-link" href="backup.php"><i class="bi bi-cloud-arrow-down"></i> Резервное копирование</a>
            <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <a class="nav-link" href="about.php"><i class="bi bi-info-circle"></i> О программе</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-5 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">Управление новостями</h2>
                <p class="text-muted mb-0">Всего новостей: <?php echo count($newsItems); ?></p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($action === 'list'): ?>
                    <form action="export.php" method="GET" class="d-inline">
                        <input type="hidden" name="filter_section" value="<?php echo htmlspecialchars($filterSection); ?>">
                        <input type="hidden" name="filter_status" value="<?php echo htmlspecialchars($filterStatus); ?>">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <button type="submit" class="btn btn-outline-secondary rounded-pill px-4">Экспорт CSV</button>
                    </form>
                    <a href="?action=add" class="btn btn-primary rounded-pill px-4">Добавить новость</a>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="glass-card mb-4">
                <h5><?php echo $action === 'add' ? 'Новая новость' : 'Редактировать новость'; ?></h5>
                <form method="POST" id="newsForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $editItem['id'] ?? ''; ?>">
                    <input type="hidden" name="content" id="content-input">
                    <input type="hidden" name="old_thumbnail" value="<?php echo $editItem['thumbnail'] ?? ''; ?>">
                    <input type="hidden" name="views" value="<?php echo $editItem['views'] ?? 0; ?>">

                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label">Заголовок</label>
                            <input type="text" name="title" class="form-control rounded-3" value="<?php echo htmlspecialchars($editItem['title'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Автор</label>
                            <input type="text" name="author" class="form-control rounded-3" value="<?php echo htmlspecialchars($editItem['author'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Раздел</label>
                            <select name="section_id" class="form-select rounded-3" required>
                                <option value="">Выберите раздел...</option>
                                <?php foreach ($sections as $sec): ?>
                                    <option value="<?php echo $sec['id']; ?>" <?php echo (isset($editItem['section_id']) && $editItem['section_id'] === $sec['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sec['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Статус</label>
                            <select name="status" class="form-select rounded-3">
                                <option value="published" <?php echo ($editItem['status'] ?? 'published') === 'published' ? 'selected' : ''; ?>>Опубликовано</option>
                                <option value="draft" <?php echo ($editItem['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Черновик</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Превью (изображение)</label>
                            <input type="file" name="thumbnail" class="form-control rounded-3" accept="image/*">
                            <?php if (!empty($editItem['thumbnail'])): ?>
                                <div class="mt-2">
                                    <img src="../<?php echo $editItem['thumbnail']; ?>" class="news-thumbnail" style="width: 150px; height: auto;">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Теги (через запятую)</label>
                            <input type="text" name="tags" class="form-control rounded-3" value="<?php echo htmlspecialchars(implode(', ', $editItem['tags'] ?? [])); ?>" placeholder="акции, новости, важно">
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="is_pinned" id="is_pinned" <?php echo !empty($editItem['is_pinned']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_pinned">Закрепить в топе</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Контент</label>
                        <div id="editor-container"><?php echo $editItem['content'] ?? ''; ?></div>
                    </div>

                    <div class="glass-card bg-light border-0 mb-4">
                        <h6 class="mb-3 text-primary"><i class="bi bi-search"></i> SEO настройки</h6>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">SEO Заголовок (Title)</label>
                                <input type="text" name="seo_title" class="form-control rounded-3" value="<?php echo htmlspecialchars($editItem['seo_title'] ?? ''); ?>" placeholder="Оставьте пустым для использования заголовка новости">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">SEO Описание (Description)</label>
                                <textarea name="seo_description" class="form-control rounded-3" rows="2"><?php echo htmlspecialchars($editItem['seo_description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Дата публикации (планирование)</label>
                            <input type="datetime-local" name="publish_at" class="form-control rounded-3" value="<?php echo isset($editItem['publish_at']) ? date('Y-m-d\TH:i', strtotime($editItem['publish_at'])) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Дата окончания (снятие с публикации)</label>
                            <div class="input-group">
                                <input type="datetime-local" name="expire_at" id="expire_at" class="form-control rounded-start-3" value="<?php echo isset($editItem['expire_at']) ? date('Y-m-d\TH:i', strtotime($editItem['expire_at'])) : ''; ?>" <?php echo empty($editItem['expire_at']) ? 'disabled' : ''; ?>>
                                <div class="input-group-text bg-white border-start-0 rounded-end-3">
                                    <input class="form-check-input mt-0 me-2" type="checkbox" id="indefinite" <?php echo empty($editItem['expire_at']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label small" for="indefinite">Бессрочно</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 d-flex gap-2">
                        <button type="button" class="btn btn-outline-info rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#previewModal" onclick="updatePreview()">Предпросмотр</button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="toggle-html">Режим HTML</button>
                    </div>

                    <div class="d-flex gap-2 mt-4 border-top pt-4">
                        <button type="submit" name="save_news" class="btn btn-primary rounded-pill px-5">Сохранить</button>
                        <a href="news.php" class="btn btn-light rounded-pill px-4">Отмена</a>
                    </div>
                </form>
            </div>
        <?php else:
            $viewMode = $_GET['view'] ?? 'table';
        ?>
            <div class="glass-card mb-4">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="q" class="form-control rounded-pill px-3" placeholder="Поиск новостей..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="filter_section" class="form-select rounded-pill">
                            <option value="">Все разделы</option>
                            <?php foreach ($sections as $sec): ?>
                                <option value="<?php echo $sec['id']; ?>" <?php echo $filterSection === $sec['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($sec['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="filter_status" class="form-select rounded-pill">
                            <option value="">Все статусы</option>
                            <option value="published" <?php echo $filterStatus === 'published' ? 'selected' : ''; ?>>Опубликовано</option>
                            <option value="draft" <?php echo $filterStatus === 'draft' ? 'selected' : ''; ?>>Черновик</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-pill flex-grow-1">Применить</button>
                        <div class="btn-group rounded-pill overflow-hidden border">
                            <a href="?view=table&<?php echo http_build_query(array_merge($_GET, ['view' => 'table'])); ?>" class="btn btn-light btn-sm <?php echo $viewMode === 'table' ? 'active' : ''; ?>"><i class="bi bi-list-ul"></i></a>
                            <a href="?view=cards&<?php echo http_build_query(array_merge($_GET, ['view' => 'cards'])); ?>" class="btn btn-light btn-sm <?php echo $viewMode === 'cards' ? 'active' : ''; ?>"><i class="bi bi-grid-3x3-gap"></i></a>
                        </div>
                    </div>
                </form>
            </div>

            <form method="POST" id="bulkForm">
                <div class="d-flex align-items-center mb-3 gap-2 px-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAll">
                        <label class="form-check-label small" for="selectAll">Выбрать все</label>
                    </div>
                    <select name="bulk_action" class="form-select form-select-sm rounded-pill w-auto">
                        <option value="">Массовые действия</option>
                        <option value="publish">Опубликовать</option>
                        <option value="delete">Удалить</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-3">Выполнить</button>
                </div>

                <?php if ($viewMode === 'table'): ?>
                    <div class="glass-card">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th width="40"></th>
                                        <th>Заголовок</th>
                                        <th>Раздел</th>
                                        <th>Просмотры</th>
                                        <th>Статус</th>
                                        <th class="text-end">Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($newsItems as $item):
                                        $sec = array_filter($sections, fn($s) => $s['id'] === $item['section_id']);
                                        $secName = !empty($sec) ? reset($sec)['name'] : 'Неизвестно';

                                        $statusClass = 'bg-success';
                                        $statusText = 'Опубликовано';
                                        $now = date('Y-m-d H:i:s');
                                        if (($item['status'] ?? 'published') === 'draft') {
                                            $statusClass = 'bg-warning text-dark';
                                            $statusText = 'Черновик';
                                        } elseif (isset($item['publish_at']) && $item['publish_at'] && $item['publish_at'] > $now) {
                                            $statusClass = 'bg-info text-dark';
                                            $statusText = 'Запланировано';
                                        } elseif (isset($item['expire_at']) && $item['expire_at'] && $item['expire_at'] < $now) {
                                            $statusClass = 'bg-secondary';
                                            $statusText = 'Архив';
                                        }
                                    ?>
                                    <tr>
                                        <td><input type="checkbox" name="selected_ids[]" value="<?php echo $item['id']; ?>" class="item-checkbox"></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($item['thumbnail'])): ?>
                                                    <img src="../<?php echo $item['thumbnail']; ?>" class="news-thumbnail me-2">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                                    <?php if (!empty($item['is_pinned'])): ?>
                                                        <i class="bi bi-pin-angle-fill text-primary ms-1" title="Закреплено"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($secName); ?></span></td>
                                        <td><small class="text-muted"><i class="bi bi-eye"></i> <?php echo $item['views'] ?? 0; ?></small></td>
                                        <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                        <td class="text-end">
                                            <a href="?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-secondary rounded-pill me-1"><i class="bi bi-pencil"></i></a>
                                            <a href="?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Вы уверены?')"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="news-card-grid">
                        <?php foreach ($newsItems as $item):
                            $sec = array_filter($sections, fn($s) => $s['id'] === $item['section_id']);
                            $secName = !empty($sec) ? reset($sec)['name'] : 'Неизвестно';
                            $now = date('Y-m-d H:i:s');
                            $isDraft = ($item['status'] ?? 'published') === 'draft';
                        ?>
                        <div class="news-item-card position-relative">
                            <div class="position-absolute top-0 start-0 m-2 z-1">
                                <input type="checkbox" name="selected_ids[]" value="<?php echo $item['id']; ?>" class="item-checkbox">
                            </div>
                            <?php if (!empty($item['is_pinned'])): ?>
                                <div class="position-absolute top-0 end-0 m-2 z-1">
                                    <span class="badge bg-primary rounded-pill"><i class="bi bi-pin-angle-fill"></i></span>
                                </div>
                            <?php endif; ?>

                            <img src="../<?php echo !empty($item['thumbnail']) ? $item['thumbnail'] : 'assets/img/no-image.jpg'; ?>" class="card-img" alt="">

                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($secName); ?></span>
                                    <small class="text-muted"><?php echo date('d.m.Y', strtotime($item['publish_at'] ?? $item['created_at'])); ?></small>
                                </div>
                                <h6 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h6>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <small class="text-muted"><i class="bi bi-eye"></i> <?php echo $item['views'] ?? 0; ?></small>
                                    <div>
                                        <a href="?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-link text-secondary p-0 me-2"><i class="bi bi-pencil"></i></a>
                                        <a href="?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($newsItems)): ?>
                    <div class="glass-card text-center py-5 text-muted">
                        <i class="bi bi-newspaper display-1 mb-3 opacity-25"></i>
                        <p>Новостей не найдено</p>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Предпросмотр</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h2 id="preview-title" class="mb-4"></h2>
                    <div id="preview-body" class="preview-content"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if (document.getElementById('editor-container')) {
            var quill = new Quill('#editor-container', {
                theme: 'snow',
                modules: {
                    toolbar: {
                        container: [
                            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                            [{ 'color': [] }, { 'background': [] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'align': [] }],
                            ['blockquote', 'code-block'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            [{ 'indent': '-1'}, { 'indent': '+1' }],
                            ['link', 'image', 'video'],
                            ['clean']
                        ],
                        handlers: {
                            image: imageHandler
                        }
                    }
                }
            });

            const htmlEditor = document.createElement('textarea');
            htmlEditor.id = 'html-editor';
            document.getElementById('editor-container').parentNode.insertBefore(htmlEditor, document.getElementById('editor-container').nextSibling);

            document.getElementById('toggle-html').onclick = function() {
                const container = document.getElementById('editor-container');
                if (container.style.display !== 'none') {
                    htmlEditor.value = quill.root.innerHTML;
                    container.style.display = 'none';
                    document.querySelector('.ql-toolbar').style.display = 'none';
                    htmlEditor.style.display = 'block';
                    this.textContent = 'Режим Визуальный';
                } else {
                    quill.root.innerHTML = htmlEditor.value;
                    container.style.display = 'block';
                    document.querySelector('.ql-toolbar').style.display = 'block';
                    htmlEditor.style.display = 'none';
                    this.textContent = 'Режим HTML';
                }
            };

            function updatePreview() {
                const content = document.getElementById('editor-container').style.display !== 'none'
                    ? quill.root.innerHTML
                    : htmlEditor.value;
                document.getElementById('preview-title').textContent = document.querySelector('input[name="title"]').value;
                document.getElementById('preview-body').innerHTML = content;
            }

            document.getElementById('indefinite').onchange = function() {
                const expireInput = document.getElementById('expire_at');
                expireInput.disabled = this.checked;
                if (this.checked) expireInput.value = '';
            };

            function imageHandler() {
                var input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                input.click();

                input.onchange = function() {
                    var file = input.files[0];
                    var formData = new FormData();
                    formData.append('image', file);

                    fetch('../api/upload.php', {
                        method: 'POST',
                        body: formData
                    }).then(res => res.json())
                    .then(result => {
                        if (result.url) {
                            var range = quill.getSelection();
                            quill.insertEmbed(range.index, 'image', result.url);
                        } else {
                            alert(result.error || 'Ошибка загрузки');
                        }
                    });
                };
            }

            document.getElementById('newsForm').onsubmit = function() {
                const content = document.getElementById('editor-container').style.display !== 'none'
                    ? quill.root.innerHTML
                    : htmlEditor.value;
                document.getElementById('content-input').value = content;
            };
        }

        if (document.getElementById('selectAll')) {
            document.getElementById('selectAll').onclick = function() {
                document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = this.checked);
            };
        }
    </script>
</body>
</html>
