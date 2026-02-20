<?php
require_once __DIR__ . '/../core/autoload.php';
require_once __DIR__ . '/../core/auth.php';

use Medical\Core\JsonStore;
use Medical\Core\UserManager;

$store = new JsonStore(__DIR__ . '/../data');
$userManager = new UserManager($store);
$userManager->initDefaults();

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Медицинская Система - Sanatorium</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@0.321.0/font/lucide.min.css">
</head>
<body>
    <div id="preloader"><div class="loader"></div></div>

    <?php if ($currentUser): ?>
    <nav class="win-nav mica-effect">
        <div class="nav-brand">
            <strong><i class="lucide-activity"></i> Medical System</strong>
        </div>
        <div class="nav-links">
            <span><?php echo htmlspecialchars($currentUser['name']); ?> (<?php echo $currentUser['role']; ?>)</span>
            <a href="index.php">Главная</a>
            <?php if ($currentUser['role'] === 'admin'): ?>
                <a href="dictionary.php">Справочник</a>
                <a href="analytics.php">Аналитика</a>
                <a href="staff.php">Персонал</a>
            <?php endif; ?>
            <a href="logout.php">Выход</a>
        </div>
    </nav>
    <?php endif; ?>

    <main class="container" style="padding: 20px;">
