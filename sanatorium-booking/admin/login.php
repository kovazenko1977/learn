<?php
session_start();
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Неверный логин или пароль";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в систему</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
    <style>
        .login-box { width: 300px; margin: 100px auto; }
        .error { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="mica-card login-box">
        <h2>Вход в админку</h2>
        <?php if (isset($error)): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post">
            <p>Логин:<br><input type="text" name="username" required style="width:100%;"></p>
            <p>Пароль:<br><input type="password" name="password" required style="width:100%;"></p>
            <button type="submit" style="width:100%;">Войти</button>
        </form>
    </div>
</body>
</html>
