<?php
if (!isset($pageTitle)) $pageTitle = 'Панель управления';
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
$root = ($currentDir === 'admin') ? '../' : '';
$base = ($currentDir === 'admin') ? '' : 'admin/';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | wesbooking</title>
    <link rel="stylesheet" href="<?php echo $root; ?>assets/css/admin.css">
    <link rel="manifest" href="<?php echo $root; ?>manifest.json">
    <meta name="theme-color" content="#0078d4">
</head>
<body class="admin-body">
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="app-logo">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    <span>wesbooking</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <?php if (hasPermission('view_dashboard')): ?>
                <a href="<?php echo ($currentFile == 'index.php') ? 'index.php' : '../index.php'; ?>" class="<?php echo $currentFile == 'index.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <span>Сегодня</span>
                </a>
                <a href="<?php echo $base; ?>dashboard.php" class="<?php echo $currentFile == 'dashboard.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    <span>Бронирования</span>
                </a>
                <?php endif; ?>

                <?php if (hasPermission('view_calendar')): ?>
                <a href="<?php echo $base; ?>calendar.php" class="<?php echo $currentFile == 'calendar.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span>Шахматка (по суткам)</span>
                </a>
                <a href="<?php echo $base; ?>hourly_grid.php" class="<?php echo $currentFile == 'hourly_grid.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    <span>График (по часам)</span>
                </a>
                <?php endif; ?>

                <?php if (hasPermission('view_analytics')): ?>
                <a href="<?php echo $base; ?>analytics.php" class="<?php echo $currentFile == 'analytics.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    <span>Аналитика</span>
                </a>
                <?php endif; ?>

                <div class="nav-divider">Ресурсы</div>

                <?php if (hasPermission('manage_planning')): ?>
                <a href="<?php echo $base; ?>planning.php" class="<?php echo $currentFile == 'planning.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    <span>Планирование</span>
                </a>
                <?php endif; ?>

                <?php if (hasPermission('manage_guests')): ?>
                <a href="<?php echo $base; ?>guests.php" class="<?php echo $currentFile == 'guests.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span>Гости</span>
                </a>
                <?php endif; ?>

                <?php if (hasPermission('manage_rooms')): ?>
                <a href="<?php echo $base; ?>rooms.php" class="<?php echo $currentFile == 'rooms.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                    <span>Номера</span>
                </a>
                <a href="<?php echo $base; ?>room_classes.php" class="<?php echo $currentFile == 'room_classes.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><line x1="9" y1="9" x2="15" y2="15"></line><line x1="15" y1="9" x2="9" y2="15"></line></svg>
                    <span>Классы номеров</span>
                </a>
                <?php endif; ?>

                <?php if (hasPermission('manage_catalog')): ?>
                <a href="<?php echo $base; ?>procedures.php" class="<?php echo $currentFile == 'procedures.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                    <span>Процедуры</span>
                </a>
                <a href="<?php echo $base; ?>services.php" class="<?php echo $currentFile == 'services.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <span>Услуги</span>
                </a>
                <a href="<?php echo $base; ?>packages.php" class="<?php echo $currentFile == 'packages.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                    <span>Пакеты</span>
                </a>
                <?php endif; ?>

                <div class="nav-divider">Настройки</div>

                <?php if (hasPermission('manage_settings')): ?>
                <a href="<?php echo $base; ?>popups.php" class="<?php echo $currentFile == 'popups.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <span>Попапы</span>
                </a>
                <a href="<?php echo $base; ?>text_blocks.php" class="<?php echo $currentFile == 'text_blocks.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span>Тексты</span>
                </a>
                <a href="<?php echo $base; ?>import_csv.php" class="<?php echo $currentFile == 'import_csv.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    <span>Импорт</span>
                </a>
                <a href="<?php echo $base; ?>constructor.php" class="<?php echo $currentFile == 'constructor.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                    <span>Конструктор форм</span>
                </a>
                <?php endif; ?>

                <a href="<?php echo $base; ?>help.php" class="<?php echo $currentFile == 'help.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <span>Справка</span>
                </a>

                <a href="<?php echo $root; ?>mobile/index.php" style="background: rgba(0,120,212,0.1); color: #0078d4; margin-top: 10px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                    <span style="font-weight: 600;">Мобильная версия</span>
                </a>

                <?php if (hasPermission('manage_users')): ?>
                <a href="<?php echo $base; ?>users.php" class="<?php echo $currentFile == 'users.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span>Пользователи</span>
                </a>
                <?php endif; ?>

                <?php if (hasPermission('manage_settings')): ?>
                <a href="<?php echo $base; ?>settings.php" class="<?php echo $currentFile == 'settings.php' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    <span>Настройки</span>
                </a>
                <?php endif; ?>
                <a href="<?php echo $base; ?>logout.php" class="logout-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    <span>Выход</span>
                </a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="content-header">
                <button id="mobile-toggle" class="mobile-only">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <h1><?php echo $pageTitle; ?></h1>

                <div class="header-search">
                    <div class="search-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" id="global-search" placeholder="Поиск гостей или броней...">
                        <div id="search-results" class="search-results-dropdown"></div>
                    </div>
                </div>
                <div class="user-profile">
                    <div style="text-align: right; margin-right: 12px;">
                        <div style="font-weight: 600; font-size: 0.9rem;"><?php echo $_SESSION['full_name'] ?? 'Пользователь'; ?></div>
                        <div style="font-size: 0.75rem; color: #666;"><?php echo ($_SESSION['role'] ?? '') === 'administrator' ? 'Администратор' : 'Сотрудник'; ?></div>
                    </div>
                    <div class="avatar"><?php echo mb_substr($_SESSION['full_name'] ?? 'U', 0, 1); ?></div>
                </div>
            </header>
            <div id="pwa-install-banner" style="display:none; background: var(--primary-color); color: white; padding: 15px 25px; justify-content: space-between; align-items: center; margin-bottom: 25px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,120,212,0.2); border: 1px solid rgba(255,255,255,0.1);">
                <span>Установите приложение wesbooking Pro на рабочий стол для быстрого доступа!</span>
                <button id="pwa-install-btn" class="btn" style="background: white; color: var(--primary-color); border: none;">Установить</button>
            </div>
            <script>
                let deferredPrompt;
                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    deferredPrompt = e;
                    document.getElementById('pwa-install-banner').style.display = 'flex';
                });

                document.getElementById('pwa-install-btn')?.addEventListener('click', async () => {
                    if (deferredPrompt) {
                        deferredPrompt.prompt();
                        const { outcome } = await deferredPrompt.userChoice;
                        if (outcome === 'accepted') {
                            document.getElementById('pwa-install-banner').style.display = 'none';
                        }
                        deferredPrompt = null;
                    }
                });

                if ('serviceWorker' in navigator) {
            </script>
            <script>
                const searchInput = document.getElementById("global-search");
                const searchResults = document.getElementById("search-results");
                let searchTimeout;

                searchInput?.addEventListener("input", (e) => {
                    clearTimeout(searchTimeout);
                    const q = e.target.value.trim();

                    if (q.length < 2) {
                        searchResults.style.display = "none";
                        return;
                    }

                    searchTimeout = setTimeout(async () => {
                        const response = await fetch(`api/search.php?q=${encodeURIComponent(q)}`);
                        const data = await response.json();

                        if (data.length > 0) {
                            searchResults.innerHTML = "";
                            data.forEach(item => {
                                const div = document.createElement("a");
                                div.className = "search-result-item";
                                div.href = item.url;
                                div.innerHTML = `
                                    <span class="title">${item.title}</span>
                                    <span class="subtitle">${item.subtitle}</span>
                                `;
                                searchResults.appendChild(div);
                            });
                            searchResults.style.display = "block";
                        } else {
                            searchResults.style.display = "none";
                        }
                    }, 300);
                });

                document.addEventListener("click", (e) => {
                    if (!e.target.closest(".search-box")) {
                        searchResults.style.display = "none";
                    }
                });
                    navigator.serviceWorker.register('<?php echo $root; ?>sw.js', { scope: '<?php echo $root; ?>' });
            </script>
            <script>
                const searchInput = document.getElementById("global-search");
                const searchResults = document.getElementById("search-results");
                let searchTimeout;

                searchInput?.addEventListener("input", (e) => {
                    clearTimeout(searchTimeout);
                    const q = e.target.value.trim();

                    if (q.length < 2) {
                        searchResults.style.display = "none";
                        return;
                    }

                    searchTimeout = setTimeout(async () => {
                        const response = await fetch(`api/search.php?q=${encodeURIComponent(q)}`);
                        const data = await response.json();

                        if (data.length > 0) {
                            searchResults.innerHTML = "";
                            data.forEach(item => {
                                const div = document.createElement("a");
                                div.className = "search-result-item";
                                div.href = item.url;
                                div.innerHTML = `
                                    <span class="title">${item.title}</span>
                                    <span class="subtitle">${item.subtitle}</span>
                                `;
                                searchResults.appendChild(div);
                            });
                            searchResults.style.display = "block";
                        } else {
                            searchResults.style.display = "none";
                        }
                    }, 300);
                });

                document.addEventListener("click", (e) => {
                    if (!e.target.closest(".search-box")) {
                        searchResults.style.display = "none";
                    }
                });
                }
            </script>
            <div class="content-body">
