<?php
require_once __DIR__ . '/../Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

$analytics = new \Medical\Core\Managers\AnalyticsManager();
$user = \Medical\Core\Auth::getUser();
$summary = $analytics->getSummary(date('Y-m-d'), date('Y-m-d')); // Summary for today

include __DIR__ . '/includes/header.php';
?>

<div style="padding: 16px;">
    <h2 style="font-weight: 400; margin-bottom: 8px;">Добро пожаловать,</h2>
    <h1 style="margin: 0; font-size: 24px; color: var(--md-primary);"><?php echo htmlspecialchars($user['name']); ?></h1>
    <p style="color: var(--md-secondary); font-size: 14px;"><?php echo htmlspecialchars($user['specialization']); ?></p>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 8px;">
    <div class="md-card" style="margin: 8px; background: #E8DEF8;">
        <div style="font-size: 12px; color: #49454F;">Процедур сегодня</div>
        <div style="font-size: 24px; font-weight: 700; margin-top: 4px;"><?php echo $summary['total_appointments']; ?></div>
    </div>
    <div class="md-card" style="margin: 8px; background: #D0E1FF;">
        <div style="font-size: 12px; color: #49454F;">Выполнено</div>
        <div style="font-size: 24px; font-weight: 700; margin-top: 4px;"><?php echo $summary['attended_count']; ?></div>
    </div>
</div>

<div class="md-card">
    <h3 style="margin-top: 0; font-weight: 500; font-size: 18px;">Быстрый доступ</h3>
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-top: 16px;">
        <a href="patients.php" style="text-decoration: none; color: inherit; text-align: center;">
            <div style="width: 48px; height: 48px; background: #F3EDF7; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                <i data-lucide="users"></i>
            </div>
            <span style="font-size: 12px;">Поиск</span>
        </a>
        <a href="attendance.php" style="text-decoration: none; color: inherit; text-align: center;">
            <div style="width: 48px; height: 48px; background: #F3EDF7; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                <i data-lucide="check-square"></i>
            </div>
            <span style="font-size: 12px;">Прием</span>
        </a>
        <a href="attendance.php?my=1" style="text-decoration: none; color: inherit; text-align: center;">
            <div style="width: 48px; height: 48px; background: #F3EDF7; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                <i data-lucide="calendar"></i>
            </div>
            <span style="font-size: 12px;">Мой график</span>
        </a>
    </div>
</div>

<div style="padding: 16px;">
    <h3 style="font-weight: 500; font-size: 18px; margin-bottom: 16px;">Загрузка кабинетов</h3>
    <?php
    $workload = $analytics->getWorkloadByCabinet(date('Y-m-d'), date('Y-m-d'));
    foreach (array_slice($workload, 0, 3) as $cab => $count):
        $max = !empty($workload) ? max($workload) : 1;
        $percent = ($count / $max) * 100;
    ?>
        <div style="margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 4px;">
                <span>Кабинет <?php echo htmlspecialchars($cab); ?></span>
                <span style="color: var(--md-secondary);"><?php echo $count; ?></span>
            </div>
            <div style="height: 4px; background: #E7E0EC; border-radius: 2px;">
                <div style="height: 100%; width: <?php echo $percent; ?>%; background: var(--md-primary); border-radius: 2px;"></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<a href="attendance.php" class="md-fab">
    <i data-lucide="plus"></i>
</a>

<?php include __DIR__ . '/includes/footer.php'; ?>
