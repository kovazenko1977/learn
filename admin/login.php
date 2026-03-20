<?php
session_start();
require_once __DIR__ . '/../includes/AuthManager.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (AuthManager::login($_POST['passcode'] ?? '')) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверный код доступа';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в систему - HIS Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f0f2f5; }
        .login-card { width: 320px; }
    </style>
</head>
<body>
    <div class="card login-card">
        <h2>Вход (Код: 123456)</h2>
        <?php if($error): ?><p style="color:red"><?= $error ?></p><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Введите 6-значный код</label>
                <input type="password" name="passcode" maxlength="6" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Войти</button>
        </form>
    </div>
</body>
</html>
