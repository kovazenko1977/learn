<?php
$user = \Medical\Core\Auth::getUser();
$current_page = basename($_SERVER['PHP_SELF']);

function isActive($page, $current_page) {
    return $page === $current_page ? 'active' : '';
}
?>
<div class="sidebar">
    <h2>WES МЕД</h2>
    <nav>
        <div class="nav-item">
            <a href="index.php" class="btn <?php echo isActive('index.php', $current_page); ?>">
                <i data-lucide="layout-dashboard" class="icon"></i> <span>Дашборд</span>
            </a>
        </div>

        <?php if (\Medical\Core\Auth::hasRole(['admin', 'doctor', 'head'])): ?>
        <div class="nav-item">
            <a href="patients.php" class="btn <?php echo isActive('patients.php', $current_page); ?>">
                <i data-lucide="users" class="icon"></i> <span>Пациенты</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if (\Medical\Core\Auth::hasRole(['admin', 'doctor', 'consultant'])): ?>
        <div class="nav-item">
            <a href="procedures_doctor.php" class="btn <?php echo isActive('procedures_doctor.php', $current_page); ?>">
                <i data-lucide="clipboard-list" class="icon"></i> <span>Назначения</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if (\Medical\Core\Auth::hasRole(['admin', 'cashier'])): ?>
        <div class="nav-item">
            <a href="procedures_cashier.php" class="btn <?php echo isActive('procedures_cashier.php', $current_page); ?>">
                <i data-lucide="credit-card" class="icon"></i> <span>Касса</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if (\Medical\Core\Auth::hasRole(['admin', 'nurse'])): ?>
        <div class="nav-item">
            <a href="procedures_nurse.php" class="btn <?php echo isActive('procedures_nurse.php', $current_page); ?>">
                <i data-lucide="check-square" class="icon"></i> <span>Кабинет</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if (\Medical\Core\Auth::hasRole(['admin', 'head'])): ?>
        <div class="nav-item">
            <a href="analytics.php" class="btn <?php echo isActive('analytics.php', $current_page); ?>">
                <i data-lucide="bar-chart-3" class="icon"></i> <span>Аналитика</span>
            </a>
        </div>
        <?php endif; ?>

        <div style="margin-top: 2rem; border-top: 1px solid var(--win-border); padding-top: 1rem;">
            <div class="nav-item">
                <a href="help.php" class="btn <?php echo isActive('help.php', $current_page); ?>">
                    <i data-lucide="help-circle" class="icon"></i> <span>Справка</span>
                </a>
            </div>
            <?php if (\Medical\Core\Auth::hasRole('admin')): ?>
            <div class="nav-item">
                <a href="settings.php" class="btn <?php echo isActive('settings.php', $current_page); ?>">
                    <i data-lucide="settings" class="icon"></i> <span>Настройки</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </nav>
</div>
