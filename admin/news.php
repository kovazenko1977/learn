<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;
use App\Models\Section;
use App\Models\NewsItem;

Auth::requireAuth();

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_news'])) {
        $id = $_POST['id'] ?? null;
        $data = [
            'id' => $id ?: uniqid(),
            'section_id' => $_POST['section_id'] ?? '',
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'publish_at' => $_POST['publish_at'] ?: null,
            'expire_at' => $_POST['expire_at'] ?: null,
            'updated_at' => date('Y-m-d H:i:s')
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
        #editor-container, #html-editor {
            height: 400px;
            background: white;
            border-radius: 0 0 10px 10px;
        }
        #html-editor {
            width: 100%;
            font-family: monospace;
            padding: 1rem;
            border: 1px solid #ccc;
            display: none;
        }
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
            <h2>Управление новостями</h2>
            <?php if ($action === 'list'): ?>
                <a href="?action=add" class="btn btn-primary rounded-pill px-4">Добавить новость</a>
            <?php endif; ?>
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
                <form method="POST" id="newsForm">
                    <input type="hidden" name="id" value="<?php echo $editItem['id'] ?? ''; ?>">
                    <input type="hidden" name="content" id="content-input">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Заголовок</label>
                            <input type="text" name="title" class="form-control rounded-3" value="<?php echo htmlspecialchars($editItem['title'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
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
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Контент</label>
                        <div id="editor-container"><?php echo $editItem['content'] ?? ''; ?></div>
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
        <?php else: ?>
            <div class="glass-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Заголовок</th>
                                <th>Раздел</th>
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
                                if (isset($item['publish_at']) && $item['publish_at'] && $item['publish_at'] > $now) {
                                    $statusClass = 'bg-warning text-dark';
                                    $statusText = 'Запланировано';
                                } elseif (isset($item['expire_at']) && $item['expire_at'] && $item['expire_at'] < $now) {
                                    $statusClass = 'bg-secondary';
                                    $statusText = 'Архив';
                                }
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['title']); ?></strong></td>
                                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($secName); ?></span></td>
                                <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                <td class="text-end">
                                    <a href="?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-secondary rounded-pill me-1"><i class="bi bi-pencil"></i></a>
                                    <a href="?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Вы уверены?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($newsItems)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Новостей еще нет</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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
    </script>
</body>
</html>
