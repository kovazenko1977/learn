<?php
require_once __DIR__ . '/../Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();

$uiSettings = [];
$settingsPath = __DIR__ . '/../data/settings.json';
if (file_exists($settingsPath)) {
    $uiSettings = json_decode(file_get_contents($settingsPath), true) ?? [];
}
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
            --win-font: <?php echo $uiSettings['font_family'] ?? "'Segoe UI', sans-serif"; ?>;
            --base-size: <?php echo $uiSettings['font_size'] ?? "16"; ?>px;
            --win-accent: <?php echo $uiSettings['accent_color'] ?? "#0078d4"; ?>;
            --win-radius: <?php echo $uiSettings['border_radius'] ?? "8"; ?>px;
        }
        body {
            font-family: var(--win-font);
            font-size: var(--base-size);
        }
        .card { border-radius: var(--win-radius); }
        .btn { border-radius: calc(var(--win-radius) / 2); }
    </style>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="mica-effect">
    <?php if (\Medical\Core\Auth::isLoggedIn()): ?>
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main-content">
            <header class="navbar card">
                <div style="flex-grow: 1;">
                    <strong><?php echo htmlspecialchars(\Medical\Core\Auth::getUser()['name']); ?></strong>
                    <span style="font-size: 0.8em; color: #666;"> (<?php echo htmlspecialchars(\Medical\Core\Auth::getUser()['role']); ?>)</span>
                </div>
                <div>
                    <a href="login.php?logout=1" class="btn">Выход</a>
                </div>
            </header>
    <?php else: ?>
        <div style="padding: 40px;">
    <?php endif; ?>
