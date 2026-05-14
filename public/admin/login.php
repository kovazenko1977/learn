<?php
require_once 'auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';

    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в админ-панель</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 400px; padding: 2rem; border-radius: 1rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1); background: #fff; }
    </style>
</head>
<body>
    <div class="login-card">
        <h3 class="text-center mb-4">Вход в систему</h3>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Логин</label>
                <input type="text" name="user" class="form-control" required autofocus autocomplete="username">
            </div>
            <div class="mb-3">
                <label class="form-label">Пароль</label>
                <input type="password" name="pass" class="form-control" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary w-100">Войти</button>
        </form>
        <div class="mt-3 text-center text-muted small">
            admin / admin123
        </div>
    </div>
</body>
</html>
