<?php
require_once __DIR__ . '/../Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();

if (isset($_GET['logout'])) {
    \Medical\Core\Auth::logout();
    header('Location: login.php');
    exit;
}

if (\Medical\Core\Auth::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (\Medical\Core\Auth::loginByCode($_POST['access_code'])) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверный код доступа';
    }
}

include __DIR__ . '/includes/header.php';
?>

<div style="height: 100vh; display: flex; flex-direction: column; justify-content: center; padding: 32px; background: #FFF;">
    <div style="text-align: center; margin-bottom: 48px;">
        <div style="width: 80px; height: 80px; background: var(--md-primary); border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; color: white; margin-bottom: 24px;">
            <i data-lucide="activity" style="width: 40px; height: 40px;"></i>
        </div>
        <h1 style="margin: 0; font-size: 28px; font-weight: 500;">WES МЕД</h1>
        <p style="color: var(--md-secondary);">Мобильная версия</p>
    </div>

    <?php if ($error): ?>
        <div style="background: #F9DEDC; color: #410E0B; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; text-align: center;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label style="display: block; font-size: 14px; margin-bottom: 8px; color: var(--md-secondary);">Код доступа (6 цифр)</label>
        <input type="password" name="access_code" class="md-input" placeholder="••••••" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus>

        <button type="submit" class="md-btn md-btn-primary" style="width: 100%; height: 48px; border-radius: 24px;">Войти</button>
    </form>

    <p style="text-align: center; margin-top: 32px; font-size: 12px; color: var(--md-secondary);">
        wes.by Коваженко С.Б.
    </p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
