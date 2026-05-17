<?php
require_once 'auth.php';
requireAdmin();
require_once __DIR__ . '/src/JsonStore.php';
$store = new \App\JsonStore(__DIR__ . '/data/popups.json');

$id = $_GET['id'] ?? null;
$popup = $id ? $store->getById($id) : [
    'title' => '',
    'text' => '',
    'image' => '',
    'animation' => 'fade',
    'code' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF validation failed');
    }

    $id = $_POST['id'] ?: uniqid();
    $title = $_POST['title'];
    $text = $_POST['text'];
    $animation = $_POST['animation'];
    $code = $_POST['code'];
    $imagePath = $_POST['existing_image'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp'
        ];
        $fileType = mime_content_type($_FILES['image']['tmp_name']);

        if (!isset($allowedMimeTypes[$fileType])) {
            die("Ошибка: Недопустимый тип файла. Разрешены только изображения.");
        }

        $ext = $allowedMimeTypes[$fileType];
        $fileName = uniqid() . '.' . $ext;
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
            if (!empty($_POST['existing_image'])) {
                secureUnlink($_POST['existing_image']);
            }
            $imagePath = '/uploads/' . $fileName;
        }
    }

    $store->set($id, [
        'id' => $id,
        'title' => $title,
        'text' => $text,
        'image' => $imagePath,
        'animation' => $animation,
        'code' => $code
    ]);

    header('Location: index.php');
    exit;
}
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $id ? 'Редактировать' : 'Создать' ?> Попап</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><?= $id ? 'Редактировать' : 'Создать' ?> попап</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($popup['image']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                            <div class="mb-3">
                                <label class="form-label">Код вызова (slug)</label>
                                <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($popup['code']) ?>" required placeholder="например: main_promo">
                                <div class="form-text">Используйте этот код в атрибуте <code>data-popup-code</code> кнопки.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Заголовок</label>
                                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($popup['title']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Текст сообщения</label>
                                <textarea name="text" class="form-control" rows="4" required><?= htmlspecialchars($popup['text']) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Изображение</label>
                                <?php if ($popup['image']): ?>
                                    <div class="mb-2">
                                        <img src="<?= htmlspecialchars($popup['image']) ?>" alt="Preview" style="max-height: 100px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="image" class="form-control" accept="image/*">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Анимация появления</label>
                                <select name="animation" class="form-select">
                                    <option value="fade" <?= $popup['animation'] == 'fade' ? 'selected' : '' ?>>Затемнение (Fade)</option>
                                    <option value="slide" <?= $popup['animation'] == 'slide' ? 'selected' : '' ?>>Слайд сверху (Slide)</option>
                                    <option value="zoom" <?= $popup['animation'] == 'zoom' ? 'selected' : '' ?>>Увеличение (Zoom)</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">Отмена</a>
                                <button type="submit" class="btn btn-primary">Сохранить</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
