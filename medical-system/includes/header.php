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
    <title>Санаторий - Медицинская система</title>
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
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main-content">
            <header class="navbar card">
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
