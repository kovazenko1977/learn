<?php
$user = \Medical\Core\Auth::getUser();
?>
<div class="sidebar mica-effect">
    <h2 style="font-size: 1.2rem; margin-bottom: 2rem;">Санаторий</h2>
    <nav>
        <div style="margin-bottom: 10px;">
            <a href="index.php" class="btn" style="display: block; width: 100%; text-align: left; margin-bottom: 5px;">
                <i data-lucide="layout-dashboard" class="icon"></i> Дашборд
            </a>
        </div>

        <?php if (\Medical\Core\Auth::hasRole(['admin', 'doctor', 'head'])): ?>
        <div style="margin-bottom: 10px;">
            <a href="patients.php" class="btn" style="display: block; width: 100%; text-align: left; margin-bottom: 5px;">
                <i data-lucide="users" class="icon"></i> Пациенты
            </a>
        </div>
        <?php endif; ?>

        <?php if (\Medical\Core\Auth::hasRole(['admin', 'doctor', 'consultant'])): ?>
        <div style="margin-bottom: 10px;">
            <a href="procedures_doctor.php" class="btn" style="display: block; width: 100%; text-align: left; margin-bottom: 5px;">
                <i data-lucide="clipboard-list" class="icon"></i> Назначения
            </a>
        </div>
        <?php endif; ?>

        <?php if (\Medical\Core\Auth::hasRole(['admin', 'cashier'])): ?>
        <div style="margin-bottom: 10px;">
            <a href="procedures_cashier.php" class="btn" style="display: block; width: 100%; text-align: left; margin-bottom: 5px;">
                <i data-lucide="credit-card" class="icon"></i> Касса
            </a>
        </div>
        <?php endif; ?>

        <?php if (\Medical\Core\Auth::hasRole(['admin', 'nurse'])): ?>
        <div style="margin-bottom: 10px;">
            <a href="procedures_nurse.php" class="btn" style="display: block; width: 100%; text-align: left; margin-bottom: 5px;">
                <i data-lucide="check-square" class="icon"></i> Процедурный кабинет
            </a>
        </div>
        <?php endif; ?>

        <?php if (\Medical\Core\Auth::hasRole(['admin', 'head'])): ?>
        <div style="margin-bottom: 10px;">
            <a href="analytics.php" class="btn" style="display: block; width: 100%; text-align: left; margin-bottom: 5px;">
                <i data-lucide="bar-chart-3" class="icon"></i> Аналитика
            </a>
        </div>
        <?php endif; ?>

        <?php if (\Medical\Core\Auth::hasRole('admin')): ?>
        <div style="margin-top: 2rem; border-top: 1px solid var(--win-border); padding-top: 1rem;">
            <a href="settings.php" class="btn" style="display: block; width: 100%; text-align: left; margin-bottom: 5px;">
                <i data-lucide="settings" class="icon"></i> Настройки
            </a>
        </div>
        <?php endif; ?>
    </nav>
</div>
