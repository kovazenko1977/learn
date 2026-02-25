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
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0078d4">
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.WES_USER_ID = "<?php echo \Medical\Core\Auth::getUser()['id'] ?? 'guest'; ?>";
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js');
            });
        }

        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            const banner = document.getElementById('pwa-install-banner');
            if (banner) banner.style.display = 'flex';
        });

        function installPWA() {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted the install prompt');
                }
                deferredPrompt = null;
                document.getElementById('pwa-install-banner').style.display = 'none';
            });
        }
    </script>
</head>
<body class="mica-effect">
    <script>
        function isMobile() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (isMobile() && !window.location.pathname.includes('/mobile/')) {
                document.getElementById('mobile-suggestion-banner').style.display = 'flex';
            }
        });
    </script>

    <!-- Mobile Suggestion Banner -->
    <div id="mobile-suggestion-banner" style="display: none; position: fixed; top: 0; left: 0; width: 100%; background: #fff8e1; border-bottom: 2px solid #ff8c00; z-index: 10001; padding: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); align-items: center; justify-content: space-between; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <i data-lucide="info" style="color: #ff8c00;"></i>
            <div style="font-size: 0.85rem; color: #333;">
                Обнаружено мобильное устройство. Использовать оптимизированную версию?
            </div>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="document.getElementById('mobile-suggestion-banner').style.display='none'" class="btn btn-sm btn-ghost" style="padding: 5px;">Нет</button>
            <a href="mobile/" class="btn btn-sm btn-primary" style="padding: 5px 10px; background: #ff8c00;">Да</a>
        </div>
    </div>

    <!-- PWA Install Banner -->
    <div id="pwa-install-banner" style="display: none; position: fixed; bottom: 0; left: 0; width: 100%; background: #fff; border-top: 2px solid var(--win-accent); z-index: 10000; padding: 15px; box-shadow: 0 -4px 20px rgba(0,0,0,0.15); align-items: center; justify-content: space-between; gap: 15px; animation: slideUp 0.5s ease;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; background: var(--win-accent); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                <i data-lucide="smartphone"></i>
            </div>
            <div>
                <div style="font-weight: 700; font-size: 0.9rem;">Установить WES МЕД</div>
                <div style="font-size: 0.75rem; color: var(--win-text-secondary);">Добавить на рабочий стол для быстрого доступа</div>
            </div>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="document.getElementById('pwa-install-banner').style.display='none'" class="btn btn-sm btn-ghost" style="padding: 8px;">Позже</button>
            <button onclick="installPWA()" class="btn btn-sm btn-primary" style="padding: 8px 16px;">Установить</button>
        </div>
    </div>

    <div id="global-preloader">
        <button type="button" onclick="document.getElementById('global-preloader').classList.add('hidden')" style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: var(--win-text-secondary); cursor: pointer; opacity: 0.5; z-index: 10001;" title="Закрыть прелоадер">
            <i data-lucide="x"></i>
        </button>
        <div class="loader-content">
            <div class="win-spinner"></div>
            <div class="loader-text">Загрузка системы...</div>
        </div>
        <script>
            // Internal failsafe in case external scripts fail
            setTimeout(function() {
                var p = document.getElementById('global-preloader');
                if (p && !p.classList.contains('hidden')) {
                    p.classList.add('hidden');
                    console.log('Preloader forced hidden by header failsafe');
                }
            }, 4000);
        </script>
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
