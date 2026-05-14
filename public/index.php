<?php
require_once 'auth.php';
requireAdmin();
require_once __DIR__ . '/../src/JsonStore.php';
$store = new \App\JsonStore(__DIR__ . '/../data/popups.json');
$popups = $store->getAll();
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Popup Manager - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f4f7f6; }
        .container { margin-top: 50px; }
        .card { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Управление всплывающими сообщениями</h1>
            <a href="edit.php" class="btn btn-primary">Создать новый попап</a>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Код</th>
                            <th>Заголовок</th>
                            <th>Анимация</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popups as $id => $popup): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($popup['code']) ?></code></td>
                            <td><?= htmlspecialchars($popup['title']) ?></td>
                            <td><?= htmlspecialchars($popup['animation']) ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="edit.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary">Изменить</a>
                                    <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirm('Вы уверены?')">
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($popups)): ?>
                        <tr>
                            <td colspan="4" class="text-center">Нет созданных попапов.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">
            <a href="demo.php" class="btn btn-link">Перейти к просмотру (Demo)</a>
        </div>
    </div>
</body>
</html>
