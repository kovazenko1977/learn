<?php
require_once __DIR__ . '/../Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Санаторий - Медицинская система</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
