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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Open+Sans:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <?php
        $settingsStore = new \Hop\Core\JsonStore('data/settings.json');
        $globalSettings = $settingsStore->read();
        $accentColor = $globalSettings['accent_color'] ?? '#0078d4';
        $primaryFont = $globalSettings['primary_font'] ?? 'Inter';
        $fontSize = $globalSettings['font_size'] ?? '15px';
        $theme = $globalSettings['theme'] ?? 'light';
        $uiStyle = $globalSettings['ui_style'] ?? 'windows';
    ?>
    <style>
        :root {
            --accent: <?php echo $accentColor; ?>;
            --font-main: <?php echo $primaryFont === 'Inter' ? "'Inter', sans-serif" : $primaryFont; ?>;
            --win-accent: <?php echo $accentColor; ?>; /* Compat */
            --win-text: var(--text-main); /* Compat */
            --win-text-secondary: var(--text-dim); /* Compat */
            --win-border: var(--border); /* Compat */
        }
        body {
            font-size: <?php echo $fontSize; ?> !important;
        }
    </style>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js');
            });
        }
    </script>
</head>
<body class="<?php echo ($theme === 'dark' ? 'dark-theme' : '') . ' ui-' . $uiStyle; ?>">
    <script>
        <?php if ($theme === 'auto'): ?>
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.body.classList.add('dark-theme');
        }
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
            if (event.matches) {
                document.body.classList.add('dark-theme');
            } else {
                document.body.classList.remove('dark-theme');
            }
        });
        <?php endif; ?>
    </script>
    <audio id="notif-sound" preload="auto">
        <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
    </audio>

    <div id="loading-overlay" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.7); backdrop-filter:blur(10px); z-index:9999; display:flex; align-items:center; justify-content:center; opacity:0; pointer-events:none; transition:opacity 0.3s;">
        <div class="win-spinner"></div>
    </div>

    <?php if (isset($_SESSION['user_role'])): ?>
    <script>
        let lastNotifCount = -1;
        function checkNotifications() {
            fetch('api_notifications.php')
                .then(r => r.json())
                .then(data => {
                    if (lastNotifCount !== -1 && data.count > lastNotifCount) {
                        const sound = document.getElementById('notif-sound');
                        if (sound) {
                            sound.play().catch(e => console.log("Sound blocked by browser policy. Interaction needed."));
                        }
                    }
                    lastNotifCount = data.count;

                    // Update badge if exists
                    const badge = document.getElementById('notif-badge');
                    const badgeSidebar = document.getElementById('notif-badge-sidebar');
                    if (badge) {
                        if (data.count > 0) {
                            badge.textContent = data.count;
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                    if (badgeSidebar) {
                        if (data.count > 0) {
                            badgeSidebar.textContent = data.count;
                            badgeSidebar.style.display = 'block';
                        } else {
                            badgeSidebar.style.display = 'none';
                        }
                    }
                });
        }
        setInterval(checkNotifications, <?php echo (int)($globalSettings['polling_interval'] ?? 10) * 1000; ?>);
        checkNotifications();
    </script>
    <aside class="sidebar mica">
        <div class="sidebar-header" style="padding: 32px 24px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width:40px; height:40px; background:var(--win-accent-gradient); border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-weight:800; font-size:20px; box-shadow: 0 8px 16px rgba(0, 120, 212, 0.25);">Х</div>
                <div style="display: flex; flex-direction: column;">
                    <span style="font-weight:800; font-size:22px; line-height:1; letter-spacing:-0.03em; color:var(--win-text);">ХОП</span>
                    <span style="font-size:10px; font-weight:700; color:var(--win-text-secondary); text-transform:uppercase; letter-spacing:0.05em; margin-top:2px;">Hospital System</span>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div style="font-size:11px; font-weight:700; color:var(--win-text-secondary); text-transform:uppercase; letter-spacing:1px; margin: 24px 12px 12px;">Основные</div>

            <a href="index.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i data-lucide="layout-grid"></i>
                <span>Дашборд</span>
            </a>

            <a href="chat.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : ''; ?>">
                <i data-lucide="message-square"></i>
                <span>Общий чат</span>
            </a>

            <a href="help.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'help.php' ? 'active' : ''; ?>">
                <i data-lucide="help-circle"></i>
                <span>Справка</span>
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
                <div style="position: relative; display: flex; align-items: center; gap: 12px; width: 100%;">
                    <i data-lucide="bell"></i>
                    <span>Уведомления</span>
                    <div id="notif-badge-sidebar" style="margin-left:auto; background:#e81123; color:white; border-radius:10px; padding: 2px 8px; font-size:10px; display:none; font-weight:700;">0</div>
                </div>
            </a>

            <div style="font-size:11px; font-weight:700; color:var(--win-text-secondary); text-transform:uppercase; letter-spacing:1px; margin: 32px 12px 12px;">Система</div>

            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <?php
                    $managementPages = ['users.php', 'services_manage.php', 'locations_manage.php', 'templates_manage.php'];
                    $isManagementOpen = in_array(basename($_SERVER['PHP_SELF']), $managementPages);
                ?>
                <div class="sidebar-group <?php echo $isManagementOpen ? 'open' : ''; ?>">
                    <div class="sidebar-group-header" onclick="this.parentElement.classList.toggle('open')">
                        <span style="font-size:11px; font-weight:700; color:var(--win-text-secondary); text-transform:uppercase; letter-spacing:1px; margin: 32px 0 12px;">Справочники</span>
                        <i data-lucide="chevron-down" class="group-chevron"></i>
                    </div>

                    <div class="sidebar-group-content">
                        <a href="users.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                            <i data-lucide="users"></i>
                            <span>Персонал</span>
                        </a>
                        <a href="services_manage.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'services_manage.php' ? 'active' : ''; ?>">
                            <i data-lucide="briefcase"></i>
                            <span>Службы</span>
                        </a>
                        <a href="locations_manage.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'locations_manage.php' ? 'active' : ''; ?>">
                            <i data-lucide="map-pin"></i>
                            <span>Объекты (Места)</span>
                        </a>
                        <a href="templates_manage.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'templates_manage.php' ? 'active' : ''; ?>">
                            <i data-lucide="copy"></i>
                            <span>Шаблоны</span>
                        </a>
                    </div>
                </div>

                <div style="font-size:11px; font-weight:700; color:var(--win-text-secondary); text-transform:uppercase; letter-spacing:1px; margin: 32px 12px 12px;">Управление</div>
                <a href="settings.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <i data-lucide="settings"></i>
                    <span>Настройки SLA</span>
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
        <div class="container" style="display:flex; justify-content:space-between; align-items:center; width:100%; padding: 0 24px; height: 100%;">
            <div class="mobile-only-header" style="display:none; align-items:center; gap:12px;">
                <button id="mobile-sidebar-toggle" style="background:none; border:none; padding:8px; cursor:pointer; color:var(--win-text);">
                    <i data-lucide="menu"></i>
                </button>
                <div style="width:32px; height:32px; background:var(--win-accent-gradient); border-radius:8px; display:flex; align-items:center; justify-content:center; color:white; font-weight:800; font-size:14px;">Х</div>
                <h1 style="font-size: 18px; margin: 0; font-weight: 800; letter-spacing: -0.02em;">ХОП</h1>
            </div>

            <div class="header-search desktop-only" style="flex: 1; max-width: 400px; position: relative;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; color: #888;"></i>
                <input type="text" placeholder="Быстрый поиск..." style="width: 100%; background: rgba(0,0,0,0.04); border: none; padding: 10px 10px 10px 40px; border-radius: 8px; font-size: 14px;">
            </div>

            <div style="display: flex; align-items: center; gap: 16px;">
                <a href="help.php" class="desktop-only" style="color: var(--win-text-secondary); text-decoration: none; display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600;">
                    <i data-lucide="help-circle" style="width: 18px; height: 18px;"></i>
                    <span>Справка</span>
                </a>
                <div class="user-pill" style="display:flex; align-items:center; gap:12px; background: rgba(0,0,0,0.03); padding: 6px 6px 6px 16px; border-radius: 20px; border: 1px solid var(--win-border);">
                    <div style="font-size: 13px; font-weight: 600; color: #333;" class="desktop-only"><?php echo $_SESSION['user_name'] ?? ''; ?></div>
                    <div style="width:32px; height:32px; background:var(--win-accent); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700;">
                        <?php echo mb_substr($_SESSION['user_name'] ?? 'U', 0, 1); ?>
                    </div>
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
            <div style="position: relative;">
                <i data-lucide="bell"></i>
                <div id="notif-badge" style="position:absolute; top:-5px; right:-5px; width:16px; height:16px; background:#e81123; color:white; border-radius:50%; font-size:10px; display:none; align-items:center; justify-content:center; font-weight:700; border:2px solid white;">0</div>
            </div>
            <span>Инфо</span>
        </a>
        <a href="profile.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i data-lucide="user"></i>
            <span>Профиль</span>
        </a>
    </nav>

    <div id="sidebar-overlay" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1001; display:none; backdrop-filter:blur(3px);"></div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggle = document.getElementById('mobile-sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        if (toggle) {
            toggle.addEventListener('click', () => {
                sidebar.style.display = 'flex';
                sidebar.style.left = '0';
                overlay.style.display = 'block';
                document.body.style.overflow = 'hidden';
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    sidebar.style.left = '-280px';
                    setTimeout(() => { sidebar.style.display = 'none'; }, 300);
                }
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            });
        }
    });
    </script>
    <?php endif; ?>

    <main>
