<?php
require_once __DIR__ . '/../Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();

$uiSettings = (new \Medical\Core\JsonStore('settings'))->getAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WES МЕД</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --win-font: <?php echo $uiSettings['font_family'] ?? "'Segoe UI Variable Display', 'Segoe UI', sans-serif"; ?>;
            --base-size: <?php echo ($uiSettings['font_size'] ?? 16); ?>px;
            --win-accent: <?php echo $uiSettings['accent_color'] ?? "#0078d4"; ?>;
            --win-radius: <?php echo ($uiSettings['border_radius'] ?? 12); ?>px;
        }
        body {
            font-family: var(--win-font);
            font-size: var(--base-size);
        }
    </style>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="mica-effect">
    <div id="global-preloader">
        <div class="loader-content">
            <div class="win-spinner"></div>
            <div class="loader-text">Загрузка системы...</div>
        </div>
    </div>

    <?php if (\Medical\Core\Auth::isLoggedIn()): ?>
        <button id="sidebar-toggle" class="btn" style="position: fixed; bottom: 20px; right: 20px; z-index: 1001; border-radius: 50%; width: 56px; height: 56px; display: none; box-shadow: 0 4px 12px rgba(0,0,0,0.2); background: var(--win-accent); color: white; border: none;">
            <i data-lucide="menu" id="toggle-icon"></i>
        </button>

        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main-content">
            <header class="navbar card">
                <button id="menu-btn-mobile" class="btn btn-sm" style="display: none; margin-right: 15px; padding: 8px;">
                    <i data-lucide="menu"></i>
                </button>
                <div style="flex-grow: 1; display: flex; align-items: center; gap: 12px;">
                    <div style="width: 32px; height: 32px; background: var(--win-accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                        <?php echo mb_substr(\Medical\Core\Auth::getUser()['name'], 0, 1); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.95rem; line-height: 1.2;">
                            <?php echo htmlspecialchars(\Medical\Core\Auth::getUser()['name']); ?>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--win-text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">
                            <?php echo htmlspecialchars(\Medical\Core\Auth::getUser()['role']); ?>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="login.php?logout=1" class="btn btn-sm">
                        <i data-lucide="log-out" class="icon" style="margin:0; width: 16px; height: 16px;"></i> Выход
                    </a>
                </div>
            </header>
    <?php else: ?>
        <div style="padding: 40px;">
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('sidebar-toggle');
            const mobileMenuBtn = document.getElementById('menu-btn-mobile');
            const sidebar = document.querySelector('.sidebar');
            const body = document.body;

            function toggleSidebar() {
                sidebar.classList.toggle('open');
                body.classList.toggle('sidebar-open');
                const icon = document.getElementById('toggle-icon');
                const menuIcon = mobileMenuBtn.querySelector('i');

                if (sidebar.classList.contains('open')) {
                    if (icon) icon.setAttribute('data-lucide', 'x');
                    if (menuIcon) menuIcon.setAttribute('data-lucide', 'x');
                } else {
                    if (icon) icon.setAttribute('data-lucide', 'menu');
                    if (menuIcon) menuIcon.setAttribute('data-lucide', 'menu');
                }
                if (window.lucide) lucide.createIcons();
            }

            if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
            if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleSidebar);

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 1024 && sidebar.classList.contains('open')) {
                    if (!sidebar.contains(e.target) && e.target !== toggleBtn && !toggleBtn.contains(e.target) && e.target !== mobileMenuBtn && !mobileMenuBtn.contains(e.target)) {
                        toggleSidebar();
                    }
                }
            });
        });
    </script>
