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

        <?php if (\Medical\Core\Auth::can('patients_view')): ?>
        <div class="nav-item">
            <a href="patients.php" class="btn <?php echo isActive('patients.php', $current_page); ?>">
                <i data-lucide="users" class="icon"></i> <span>Регистратура</span>
            </a>
        </div>
        <?php endif; ?>

        <?php
        $sys = (new \Medical\Core\JsonStore('settings'))->getAll();
        if ($sys['is_booking_enabled'] ?? false): ?>
        <div class="nav-item">
            <a href="booking.php" class="btn <?php echo isActive('booking.php', $current_page); ?>">
                <i data-lucide="calendar-check" class="icon"></i> <span>Бронирование</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if (\Medical\Core\Auth::can('procedures_assign')): ?>
        <div class="nav-item">
            <a href="procedures_doctor.php" class="btn <?php echo isActive('procedures_doctor.php', $current_page); ?>">
                <i data-lucide="clipboard-list" class="icon"></i> <span>Назначения</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if (\Medical\Core\Auth::can('procedures_assign') || \Medical\Core\Auth::can('procedures_nurse')): ?>
        <div class="nav-item">
            <a href="procedures_map.php" class="btn <?php echo isActive('procedures_map.php', $current_page); ?>">
                <i data-lucide="calendar-range" class="icon"></i> <span>Карта загрузки</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if (\Medical\Core\Auth::can('lab_view')): ?>
        <div class="nav-item">
            <a href="lab_results.php" class="btn <?php echo isActive('lab_results.php', $current_page); ?>">
                <i data-lucide="microscope" class="icon"></i> <span>Исследования</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if (\Medical\Core\Auth::can('finance_pay')): ?>
        <div class="nav-item">
            <a href="procedures_cashier.php" class="btn <?php echo isActive('procedures_cashier.php', $current_page); ?>">
                <i data-lucide="credit-card" class="icon"></i> <span>Касса</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if (\Medical\Core\Auth::can('procedures_nurse')): ?>
        <div class="nav-item">
            <a href="procedures_nurse.php" class="btn <?php echo isActive('procedures_nurse.php', $current_page); ?>">
                <i data-lucide="check-square" class="icon"></i> <span>Кабинет</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if (\Medical\Core\Auth::can('analytics_view')): ?>
        <div class="nav-item">
            <a href="analytics.php" class="btn <?php echo isActive('analytics.php', $current_page); ?>">
                <i data-lucide="bar-chart-3" class="icon"></i> <span>Аналитика</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if (\Medical\Core\Auth::can('logs_view')): ?>
        <div class="nav-item">
            <a href="logs.php" class="btn <?php echo isActive('logs.php', $current_page); ?>">
                <i data-lucide="scroll-text" class="icon"></i> <span>Журнал</span>
            </a>
        </div>
        <?php endif; ?>

        <div style="margin-top: 2rem; border-top: 1px solid var(--win-border); padding-top: 1rem;">
            <div class="nav-item">
                <a href="help.php" class="btn <?php echo isActive('help.php', $current_page); ?>">
                    <i data-lucide="help-circle" class="icon"></i> <span>Справка</span>
                </a>
            </div>
            <?php if (\Medical\Core\Auth::can('settings_staff') || \Medical\Core\Auth::can('settings_procs') || \Medical\Core\Auth::can('settings_system')): ?>
            <div class="nav-item">
                <a href="settings.php" class="btn <?php echo isActive('settings.php', $current_page); ?>">
                    <i data-lucide="settings" class="icon"></i> <span>Настройки</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </nav>
</div>
