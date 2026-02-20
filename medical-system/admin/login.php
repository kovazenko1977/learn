<?php
require_once __DIR__ . '/../core/autoload.php';
require_once __DIR__ . '/../core/auth.php';

use Medical\Core\JsonStore;
use Medical\Core\UserManager;

$store = new JsonStore(__DIR__ . '/../data');
$userManager = new UserManager($store);
$userManager->initDefaults();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['access_code'] ?? '';
    $user = false;

    if (!empty($code)) {
        $user = $userManager->loginByCode($code);
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $user = $userManager->login($username, $password);
    }

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверные данные для входа';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход - Медицинская Система</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-box { width: 400px; text-align: center; }
        .code-input { font-size: 32px; letter-spacing: 10px; text-align: center; width: 100%; border: 2px solid var(--win-accent); }
    </style>
</head>
<body>
    <div class="login-box win-card mica-effect">
        <h2 style="margin-bottom: 30px;">Вход в систему</h2>

        <?php if ($error): ?>
            <div class="status-red" style="padding: 10px; border-radius: 4px; margin-bottom: 20px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" id="codeForm">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px; font-weight: 500;">Введите 6-значный код доступа</label>
                <input type="text" name="access_code" maxlength="6" class="form-win code-input" placeholder="••••••" autofocus>
            </div>
            <button type="submit" class="btn-win" style="width: 100%;">Войти по коду</button>
            <div style="margin-top: 15px;">
                <a href="#" onclick="toggleMode(); return false;" id="toggleLink">Вход по логину/паролю</a>
            </div>
        </form>

        <form method="POST" id="standardForm" style="display: none;">
            <div style="margin-bottom: 15px; text-align: left;">
                <label>Логин</label>
                <input type="text" name="username" class="form-win">
            </div>
            <div style="margin-bottom: 20px; text-align: left;">
                <label>Пароль</label>
                <input type="password" name="password" class="form-win">
            </div>
            <button type="submit" class="btn-win" style="width: 100%;">Войти</button>
            <div style="margin-top: 15px;">
                <a href="#" onclick="toggleMode(); return false;">Вход по коду доступа</a>
            </div>
        </form>
    </div>

    <script>
        function toggleMode() {
            const codeForm = document.getElementById('codeForm');
            const standardForm = document.getElementById('standardForm');
            if (codeForm.style.display === 'none') {
                codeForm.style.display = 'block';
                standardForm.style.display = 'none';
            } else {
                codeForm.style.display = 'none';
                standardForm.style.display = 'block';
            }
        }
    </script>
</body>
</html>
