<?php
require_once 'auth.php';
requireAdmin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF validation failed');
    }

    $config = getConfig();
    $oldPass = $_POST['old_pass'] ?? '';
    $newPass = $_POST['new_pass'] ?? '';
    $confirmPass = $_POST['confirm_pass'] ?? '';

    if (!password_verify($oldPass, $config['admin_pass_hash'])) {
        $error = 'Неверный текущий пароль';
    } elseif ($newPass !== $confirmPass) {
        $error = 'Новые пароли не совпадают';
    } elseif (strlen($newPass) < 6) {
        $error = 'Новый пароль должен быть не менее 6 символов';
    } else {
        $config['admin_pass_hash'] = password_hash($newPass, PASSWORD_BCRYPT);
        saveConfig($config);
        $message = 'Пароль успешно изменен';
    }
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Настройки - Popup Manager</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Настройки</h4>
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">Назад</a>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                            <div class="mb-3">
                                <label class="form-label">Текущий пароль</label>
                                <input type="password" name="old_pass" class="form-control" required>
                            </div>

                            <hr>

                            <div class="mb-3">
                                <label class="form-label">Новый пароль</label>
                                <input type="password" name="new_pass" class="form-control" required minlength="6">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Подтвердите новый пароль</label>
                                <input type="password" name="confirm_pass" class="form-control" required minlength="6">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Сохранить изменения</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
