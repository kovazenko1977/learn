<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ХОП - Хозяйственно-Оперативные Поручения</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <header class="mica">
        <div class="container" style="display:flex; justify-content:space-between; align-items:center; width:100%;">
            <h1>ХОП</h1>
            <div class="user-info" style="font-size: 14px;">
                <?php echo $_SESSION['user_name'] ?? ''; ?>
                <a href="logout.php" style="margin-left:10px; color:var(--win-accent); text-decoration:none;">Выход</a>
            </div>
        </div>
    </header>

    <?php if (isset($_SESSION['user_role'])): ?>
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
            <span>Уведомления</span>
        </a>
        <a href="profile.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i data-lucide="user"></i>
            <span>Профиль</span>
        </a>
    </nav>
    <?php endif; ?>

    <main style="padding-bottom: 80px;">
