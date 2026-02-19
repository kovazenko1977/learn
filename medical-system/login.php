<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Ошибка безопасности (CSRF)';
    } else {
        $code = $_POST['code'] ?? '';
        if (\Medical\Core\Auth::loginByCode($code)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Неверный код доступа';
        }
    }
}

if (isset($_GET['logout'])) {
    \Medical\Core\Auth::logout();
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="login-container" style="max-width: 400px; margin: 100px auto;">
    <div class="card mica-effect" style="text-align: center; padding: 40px;">
        <h1 style="margin-bottom: 30px;">Вход в систему</h1>

        <?php if ($error): ?>
            <div style="color: #d83b01; margin-bottom: 20px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; text-align: left;">Код доступа (6 цифр)</label>
                <input type="password" name="code" maxlength="6" style="width: 100%; font-size: 24px; text-align: center; letter-spacing: 10px;" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">Войти</button>
        </form>
        <div style="margin-top: 24px; border-top: 1px solid var(--win-border); padding-top: 24px;">
            <a href="mobile/login.php" style="color: var(--win-accent); text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i data-lucide="smartphone" style="width:18px; height:18px;"></i> Перейти в мобильную версию
            </a>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
