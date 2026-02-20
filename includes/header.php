<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ХОП — Информационная система больницы</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0078d4">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
    <div id="loading-overlay" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.7); backdrop-filter:blur(10px); z-index:9999; display:flex; align-items:center; justify-content:center; opacity:0; pointer-events:none; transition:opacity 0.3s;">
        <div class="win-spinner"></div>
    </div>

    <?php if (isset($_SESSION['user_role'])): ?>
    <aside class="sidebar mica">
        <div class="sidebar-header" style="padding: 24px;">
            <div style="width:40px; height:40px; background:var(--win-accent); border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-weight:800; font-size:18px; box-shadow: 0 4px 12px rgba(0, 120, 212, 0.3);">Х</div>
            <span style="margin-left:12px; font-weight:800; font-size:20px; letter-spacing:-0.5px; color:#1a1a1a;">ХОП</span>
        </div>

        <nav class="sidebar-nav" style="padding: 0 12px;">
            <div style="font-size:11px; font-weight:700; color:var(--win-text-secondary); text-transform:uppercase; letter-spacing:1px; margin: 24px 12px 12px;">Основные</div>

            <a href="index.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i data-lucide="layout-grid"></i>
                <span>Дашборд</span>
            </a>

            <a href="chat.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : ''; ?>">
                <i data-lucide="message-square"></i>
                <span>Общий чат</span>
            </a>

            <?php if ($_SESSION['user_role'] === 'initiator' || $_SESSION['user_role'] === 'admin'): ?>
            <a href="create.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'create.php' ? 'active' : ''; ?>">
                <i data-lucide="file-plus"></i>
                <span>Новая заявка</span>
            </a>
            <?php endif; ?>

            <?php if (in_array($_SESSION['user_role'], ['manager', 'admin'])): ?>
            <a href="analytics.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
                <i data-lucide="bar-chart-big"></i>
                <span>Аналитика</span>
            </a>
            <?php endif; ?>

            <a href="notifications.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
                <i data-lucide="bell"></i>
                <span>Уведомления</span>
            </a>

            <div style="font-size:11px; font-weight:700; color:var(--win-text-secondary); text-transform:uppercase; letter-spacing:1px; margin: 32px 12px 12px;">Система</div>

            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="users.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <i data-lucide="users"></i>
                    <span>Персонал</span>
                </a>
                <a href="services_manage.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'services_manage.php' ? 'active' : ''; ?>">
                    <i data-lucide="briefcase"></i>
                    <span>Службы</span>
                </a>
                <a href="templates_manage.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'templates_manage.php' ? 'active' : ''; ?>">
                    <i data-lucide="copy"></i>
                    <span>Шаблоны</span>
                </a>
                <a href="settings.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <i data-lucide="settings"></i>
                    <span>Настройки</span>
                </a>
            <?php endif; ?>

            <a href="profile.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <i data-lucide="user-circle"></i>
                <span>Мой профиль</span>
            </a>

            <a href="logout.php" class="sidebar-item" style="margin-top:20px; color: #e81123;">
                <i data-lucide="log-out"></i>
                <span>Выйти</span>
            </a>
        </nav>

        <div class="sidebar-footer" style="margin-top: auto; padding: 24px; font-size: 11px; color: var(--win-text-secondary); border-top: 1px solid var(--win-border);">
            <div style="font-weight: 700; color: #1a1a1a; margin-bottom: 4px;">Разработка WES.BY</div>
            <div>© 2024 Коваженко С.Б.</div>
        </div>
    </aside>

    <header class="mica">
        <div class="container" style="display:flex; justify-content:space-between; align-items:center; width:100%; padding: 0 24px;">
            <div class="mobile-only-header" style="display:none; align-items:center; gap:12px;">
                <div style="width:32px; height:32px; background:var(--win-accent); border-radius:8px; display:flex; align-items:center; justify-content:center; color:white; font-weight:800; font-size:14px;">Х</div>
                <h1 style="font-size: 18px; margin: 0; font-weight: 800; letter-spacing: -0.5px;">ХОП</h1>
            </div>

            <div class="header-search desktop-only" style="flex: 1; max-width: 400px; position: relative;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; color: #888;"></i>
                <input type="text" placeholder="Быстрый поиск..." style="width: 100%; background: rgba(0,0,0,0.04); border: none; padding: 10px 10px 10px 40px; border-radius: 8px; font-size: 14px;">
            </div>

            <div class="user-pill" style="display:flex; align-items:center; gap:12px; background: rgba(0,0,0,0.03); padding: 6px 6px 6px 16px; border-radius: 20px; border: 1px solid var(--win-border);">
                <div style="font-size: 13px; font-weight: 600; color: #333;" class="desktop-only"><?php echo $_SESSION['user_name'] ?? ''; ?></div>
                <div style="width:32px; height:32px; background:var(--win-accent); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700;">
                    <?php echo mb_substr($_SESSION['user_name'] ?? 'U', 0, 1); ?>
                </div>
            </div>
        </div>
    </header>

    <nav class="bottom-nav mica">
        <a href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i data-lucide="layout-grid"></i>
            <span>Задачи</span>
        </a>
        <a href="chat.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : ''; ?>">
            <i data-lucide="message-square"></i>
            <span>Чат</span>
        </a>
        <?php if ($_SESSION['user_role'] === 'initiator' || $_SESSION['user_role'] === 'admin'): ?>
        <a href="create.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'create.php' ? 'active' : ''; ?>">
            <div style="width:48px; height:48px; background:var(--win-accent); border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; box-shadow: 0 4px 12px rgba(0,120,212,0.4); margin-bottom: 24px;">
                <i data-lucide="plus" style="width:28px; height:28px;"></i>
            </div>
        </a>
        <?php endif; ?>
        <a href="notifications.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
            <i data-lucide="bell"></i>
            <span>Инфо</span>
        </a>
        <a href="profile.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i data-lucide="user"></i>
            <span>Профиль</span>
        </a>
    </nav>
    <?php endif; ?>

    <main>
