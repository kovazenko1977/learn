<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('analytics_view')) {
    die("Доступ ограничен");
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 32px;">
    <h1>Отчеты и Аналитика</h1>
    <p style="color: var(--win-text-secondary);">Выберите категорию для формирования подробного отчета</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
    <a href="reports_patients.php" class="card mica-effect report-card" style="text-decoration: none; color: inherit; transition: transform 0.2s, box-shadow 0.2s;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="width: 64px; height: 64px; background: rgba(0,120,212,0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center; color: var(--win-accent);">
                <i data-lucide="users" style="width: 32px; height: 32px;"></i>
            </div>
            <div>
                <h2 style="margin: 0; font-size: 1.25rem;">Пациенты</h2>
                <p style="margin: 5px 0 0 0; font-size: 0.9rem; color: var(--win-text-secondary);">Демография, заезды, диагнозы</p>
            </div>
        </div>
        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <span class="btn btn-sm btn-ghost">Подробнее <i data-lucide="chevron-right" class="icon" style="margin:0 0 0 5px;"></i></span>
        </div>
    </a>

    <a href="reports_procedures.php" class="card mica-effect report-card" style="text-decoration: none; color: inherit; transition: transform 0.2s, box-shadow 0.2s;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="width: 64px; height: 64px; background: rgba(16,124,16,0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center; color: #107c10;">
                <i data-lucide="activity" style="width: 32px; height: 32px;"></i>
            </div>
            <div>
                <h2 style="margin: 0; font-size: 1.25rem;">Процедуры</h2>
                <p style="margin: 5px 0 0 0; font-size: 0.9rem; color: var(--win-text-secondary);">Популярность, нагрузка, кабинеты</p>
            </div>
        </div>
        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <span class="btn btn-sm btn-ghost">Подробнее <i data-lucide="chevron-right" class="icon" style="margin:0 0 0 5px;"></i></span>
        </div>
    </a>

    <a href="reports_cashier.php" class="card mica-effect report-card" style="text-decoration: none; color: inherit; transition: transform 0.2s, box-shadow 0.2s;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="width: 64px; height: 64px; background: rgba(209,52,56,0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center; color: #d13438;">
                <i data-lucide="credit-card" style="width: 32px; height: 32px;"></i>
            </div>
            <div>
                <h2 style="margin: 0; font-size: 1.25rem;">Касса</h2>
                <p style="margin: 5px 0 0 0; font-size: 0.9rem; color: var(--win-text-secondary);">Выручка, долги, средний чек</p>
            </div>
        </div>
        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <span class="btn btn-sm btn-ghost">Подробнее <i data-lucide="chevron-right" class="icon" style="margin:0 0 0 5px;"></i></span>
        </div>
    </a>

    <a href="reports_doctors.php" class="card mica-effect report-card" style="text-decoration: none; color: inherit; transition: transform 0.2s, box-shadow 0.2s;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="width: 64px; height: 64px; background: rgba(139,68,213,0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center; color: #8b44d5;">
                <i data-lucide="stethoscope" style="width: 32px; height: 32px;"></i>
            </div>
            <div>
                <h2 style="margin: 0; font-size: 1.25rem;">Врачи</h2>
                <p style="margin: 5px 0 0 0; font-size: 0.9rem; color: var(--win-text-secondary);">Эффективность, назначения, нагрузка</p>
            </div>
        </div>
        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <span class="btn btn-sm btn-ghost">Подробнее <i data-lucide="chevron-right" class="icon" style="margin:0 0 0 5px;"></i></span>
        </div>
    </a>
</div>

<style>
.report-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
