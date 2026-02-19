<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

require_once __DIR__ . '/includes/header.php';

$analytics = new \Medical\Core\Managers\AnalyticsManager();
$scheduleManager = new \Medical\Core\Managers\ScheduleManager();

// Housekeeping: auto-cancel unpaid procedures in the past
$scheduleManager->autoCancelUnpaid();

$summary = $analytics->getSummary();
?>
<h1>Панель управления</h1>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px;">
    <a href="patients.php" class="card-link">
        <div class="card mica-effect drill-down-card">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 12px;">
                <div style="background: rgba(0,120,212,0.1); padding: 10px; border-radius: 10px; color: var(--win-accent);">
                    <i data-lucide="users" style="width: 24px; height: 24px;"></i>
                </div>
                <h3 style="color: var(--win-text-secondary); font-size: 0.95rem; margin: 0;">Пациентов всего</h3>
            </div>
            <div style="font-size: 2.2rem; font-weight: 700;"><?php echo $summary['total_patients']; ?></div>
            <div class="drill-down-hint">Подробнее <i data-lucide="chevron-right" style="width:14px;height:14px;"></i></div>
        </div>
    </a>
    <a href="analytics.php" class="card-link">
        <div class="card mica-effect drill-down-card">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 12px;">
                <div style="background: rgba(0,120,212,0.1); padding: 10px; border-radius: 10px; color: var(--win-accent);">
                    <i data-lucide="clipboard-list" style="width: 24px; height: 24px;"></i>
                </div>
                <h3 style="color: var(--win-text-secondary); font-size: 0.95rem; margin: 0;">Назначено процедур</h3>
            </div>
            <div style="font-size: 2.2rem; font-weight: 700;"><?php echo $summary['total_appointments']; ?></div>
            <div class="drill-down-hint">Подробнее <i data-lucide="chevron-right" style="width:14px;height:14px;"></i></div>
        </div>
    </a>
    <?php if (\Medical\Core\Auth::can('finance_view')): ?>
    <a href="analytics.php" class="card-link">
        <div class="card mica-effect drill-down-card">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 12px;">
                <div style="background: rgba(16,124,16,0.1); padding: 10px; border-radius: 10px; color: #107c10;">
                    <i data-lucide="check-circle" style="width: 24px; height: 24px;"></i>
                </div>
                <h3 style="color: var(--win-text-secondary); font-size: 0.95rem; margin: 0;">Оказано услуг</h3>
            </div>
            <div style="font-size: 2.2rem; font-weight: 700;"><?php echo $summary['attended_count']; ?></div>
            <div class="drill-down-hint">Подробнее <i data-lucide="chevron-right" style="width:14px;height:14px;"></i></div>
        </div>
    </a>
    <a href="analytics.php" class="card-link">
        <div class="card mica-effect drill-down-card">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 12px;">
                <div style="background: rgba(16,124,16,0.1); padding: 10px; border-radius: 10px; color: #107c10;">
                    <i data-lucide="banknote" style="width: 24px; height: 24px;"></i>
                </div>
                <h3 style="color: var(--win-text-secondary); font-size: 0.95rem; margin: 0;">Выручка</h3>
            </div>
            <div style="font-size: 2.2rem; font-weight: 700; color: #107c10;"><?php echo number_format($summary['total_revenue'], 0, ',', ' '); ?> <span style="font-size: 1.2rem;">₽</span></div>
            <div class="drill-down-hint">Подробнее <i data-lucide="chevron-right" style="width:14px;height:14px;"></i></div>
        </div>
    </a>
    <?php endif; ?>
</div>

<style>
.card-link { text-decoration: none; color: inherit; display: block; }
.drill-down-card { transition: transform 0.2s, box-shadow 0.2s; position: relative; overflow: hidden; }
.drill-down-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
.drill-down-hint {
    position: absolute; bottom: 0; right: 0; background: rgba(0,120,212,0.1);
    padding: 4px 12px; font-size: 0.7rem; border-top-left-radius: 8px; color: var(--win-accent);
    display: flex; align-items: center; gap: 4px; opacity: 0; transition: opacity 0.2s;
}
.drill-down-card:hover .drill-down-hint { opacity: 1; }
</style>

<div style="margin-top: 32px; display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px;">
    <?php if (\Medical\Core\Auth::getUser()['role'] === 'doctor'): ?>
    <div class="card mica-effect">
        <h2 style="margin-bottom: 24px;">Мои пациенты</h2>
        <?php
        $patientManager = new \Medical\Core\Managers\PatientManager();
        $myPatients = array_filter($patientManager->getAll(), function($p) {
            return ($p['treating_doctor'] ?? '') === \Medical\Core\Auth::getUser()['name'];
        });
        if (empty($myPatients)):
        ?>
            <p style="color: var(--win-text-secondary); text-align: center; padding: 20px;">У вас пока нет прикрепленных пациентов</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ФИО</th>
                        <th>№ Карты</th>
                        <th style="text-align: right;">Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($myPatients, 0, 5) as $p): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                        <td><code><?php echo htmlspecialchars($p['card_number'] ?? '-'); ?></code></td>
                        <td style="text-align: right;">
                            <a href="procedures_doctor.php?patient_id=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary">Назначить</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($myPatients) > 5): ?>
                <div style="margin-top: 15px; text-align: center;">
                    <a href="patients.php?doctor=<?php echo urlencode(\Medical\Core\Auth::getUser()['name']); ?>" style="color: var(--win-accent); text-decoration: none; font-size: 0.9rem;">Показать всех (<?php echo count($myPatients); ?>)</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="card mica-effect">
        <h2 style="margin-bottom: 24px;">Быстрые действия</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <?php if (\Medical\Core\Auth::can('patients_edit')): ?>
            <a href="patients.php" class="btn btn-primary" style="padding: 16px;">
                <i data-lucide="user-plus" class="icon"></i> Регистрация пациента
            </a>
            <?php endif; ?>
            <?php if (\Medical\Core\Auth::can('procedures_assign')): ?>
            <a href="procedures_doctor.php" class="btn" style="padding: 16px;">
                <i data-lucide="calendar" class="icon"></i> График процедур
            </a>
            <?php endif; ?>
            <?php if (\Medical\Core\Auth::can('analytics_view')): ?>
            <a href="analytics.php" class="btn" style="padding: 16px;">
                <i data-lucide="bar-chart-3" class="icon"></i> Аналитика
            </a>
            <?php endif; ?>
            <?php if (\Medical\Core\Auth::can('finance_pay')): ?>
            <a href="procedures_cashier.php" class="btn" style="padding: 16px;">
                <i data-lucide="credit-card" class="icon"></i> Касса
            </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card mica-effect">
        <h2 style="margin-bottom: 24px;">Загрузка кабинетов</h2>
        <?php
        $workload = $analytics->getWorkloadByCabinet();
        if (empty($workload)):
        ?>
            <p style="color: var(--win-text-secondary); text-align: center; padding: 20px;">Нет данных о загрузке</p>
        <?php
        else:
            foreach ($workload as $cab => $count):
                $max = max($workload);
                $percent = ($count / $max) * 100;
        ?>
                <div style="margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 0.9rem;">
                        <span style="font-weight: 500;">Кабинет <?php echo htmlspecialchars($cab); ?></span>
                        <span style="color: var(--win-text-secondary);"><?php echo $count; ?> проц.</span>
                    </div>
                    <div style="height: 6px; background: rgba(0,0,0,0.05); border-radius: 3px; overflow: hidden;">
                        <div style="height: 100%; width: <?php echo $percent; ?>%; background: var(--win-accent); border-radius: 3px;"></div>
                    </div>
                </div>
        <?php
            endforeach;
        endif;
        ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
