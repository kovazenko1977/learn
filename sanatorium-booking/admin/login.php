<?php
require_once '../core/autoload.php';

use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Users\UserManager;

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (isset($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store = new JsonStore(__DIR__ . '/../data');
    $userManager = new UserManager($store);

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $access_code = $_POST['access_code'] ?? '';

    $user = null;
    if (!empty($access_code)) {
        $user = $userManager->authenticateByCode($access_code);
    } elseif (!empty($username) && !empty($password)) {
        $user = $userManager->authenticate($username, $password);
    }

    if ($user) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['permissions'] = $user['permissions'] ?? [];

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход | Sanatorium Booking</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
    <style>
        body.login-body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }
        .login-box {
            width: 100%;
            max-width: 360px;
        }
        .error {
            background: rgba(216, 59, 1, 0.1);
            color: #d83b01;
            padding: 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            border: 1px solid rgba(216, 59, 1, 0.2);
        }
    </style>
</head>
<body class="admin-body login-body">
    <div class="mica-card login-box">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="color: var(--primary-color); margin-bottom: 10px;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            </div>
            <h2 style="margin-bottom: 5px;">Sanatorium</h2>
            <p style="color: #666; font-size: 0.9rem;">Вход в панель управления</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div id="code-login-form">
            <form method="post">
                <label>Введите 6-значный код доступа</label>
                <input type="text" name="access_code" maxlength="6" pattern="\d{6}" required autofocus placeholder="123456" style="font-size: 1.5rem; text-align: center; letter-spacing: 0.5rem;">
                <button type="submit" class="btn" style="width: 100%; padding: 12px; margin-top: 10px;">Войти по коду</button>
            </form>
            <div style="text-align: center; margin-top: 15px;">
                <a href="#" onclick="document.getElementById('code-login-form').style.display='none'; document.getElementById('standard-login-form').style.display='block'; return false;" style="font-size: 0.85rem; color: var(--primary-color);">Вход по логину/паролю</a>
            </div>
        </div>

        <div id="standard-login-form" style="display: none;">
            <form method="post">
                <label>Логин</label>
                <input type="text" name="username" placeholder="admin">

                <label>Пароль</label>
                <input type="password" name="password" placeholder="••••••••">

                <button type="submit" class="btn" style="width: 100%; padding: 12px; margin-top: 10px;">Войти в систему</button>
            </form>
            <div style="text-align: center; margin-top: 15px;">
                <a href="#" onclick="document.getElementById('standard-login-form').style.display='none'; document.getElementById('code-login-form').style.display='block'; return false;" style="font-size: 0.85rem; color: var(--primary-color);">Вход по коду доступа</a>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: center; font-size: 0.8rem; color: #888;">
            &copy; <?php echo date('Y'); ?> Sanatorium Booking System
        </div>
    </div>
</body>
</html>
