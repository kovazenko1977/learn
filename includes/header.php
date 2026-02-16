<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ХОП - Хозяйственно-Оперативные Поручения</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0078d4">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js');
            });
        }
    </script>
</head>
<body>
    <?php if (isset($_SESSION['user_role'])): ?>
    <aside class="sidebar mica">
        <a href="index.php" class="sidebar-logo">ХОП</a>
        <nav class="sidebar-nav">
            <a href="index.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i data-lucide="layout-dashboard"></i>
                <span>Заявки</span>
            </a>
            <?php if ($_SESSION['user_role'] === 'initiator' || $_SESSION['user_role'] === 'admin'): ?>
            <a href="create.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'create.php' ? 'active' : ''; ?>">
                <i data-lucide="plus-circle"></i>
                <span>Создать заявку</span>
            </a>
            <?php endif; ?>
            <?php if (in_array($_SESSION['user_role'], ['manager', 'admin'])): ?>
            <a href="analytics.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
                <i data-lucide="bar-chart-3"></i>
                <span>Аналитика</span>
            </a>
            <?php endif; ?>
            <a href="notifications.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
                <i data-lucide="bell"></i>
                <span>Уведомления</span>
            </a>
            <a href="profile.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <i data-lucide="user"></i>
                <span>Профиль</span>
            </a>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <div style="margin-top:20px; padding-top:20px; border-top:1px solid var(--win-border);">
                <a href="users.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <i data-lucide="users"></i>
                    <span>Пользователи</span>
                </a>
                <a href="services_manage.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'services_manage.php' ? 'active' : ''; ?>">
                    <i data-lucide="briefcase"></i>
                    <span>Службы</span>
                </a>
                <a href="settings.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <i data-lucide="settings"></i>
                    <span>Настройки</span>
                </a>
            </div>
            <?php endif; ?>
            <div style="margin-top:auto; padding-top:20px; font-size:10px; color:var(--win-text-secondary); text-align:center;">
                Разработка: <a href="https://wes.by" target="_blank" style="color:inherit;">WES.BY</a><br>
                Коваженко С.Б.
            </div>
        </nav>
    </aside>

    <header class="mica">
        <div class="container" style="display:flex; justify-content:space-between; align-items:center; width:100%; margin:0 auto;">
            <h1 class="mobile-only-header" style="font-size: 18px; margin: 0; font-weight: 600;">ХОП</h1>
            <div class="user-info" style="font-size: 14px; margin-left: auto;">
                <span class="desktop-only"><?php echo $_SESSION['user_name'] ?? ''; ?></span>
                <a href="logout.php" style="margin-left:10px; color:var(--win-accent); text-decoration:none;"><i data-lucide="log-out" style="width:16px; vertical-align:middle;"></i> <span class="desktop-only">Выход</span></a>
            </div>
        </div>
    </header>

    <nav class="bottom-nav mica">
        <a href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i data-lucide="layout-dashboard"></i>
            <span>Заявки</span>
        </a>
        <?php if ($_SESSION['user_role'] === 'initiator' || $_SESSION['user_role'] === 'admin'): ?>
        <a href="create.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'create.php' ? 'active' : ''; ?>">
            <i data-lucide="plus-circle"></i>
            <span>Создать</span>
        </a>
        <?php endif; ?>
        <a href="notifications.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
            <i data-lucide="bell"></i>
            <span>Увед.</span>
        </a>
        <a href="profile.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i data-lucide="user"></i>
            <span>Профиль</span>
        </a>
    </nav>
    <?php endif; ?>

    <main style="padding-bottom: 80px;">
