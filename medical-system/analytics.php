<?php
require_once __DIR__ . '/includes/header.php';
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::hasRole(['admin', 'head'])) {
    echo '<div class="card mica-effect"><h2>Доступ ограничен</h2><p>Только администратор или начмед могут просматривать аналитику.</p></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$analytics = new \Medical\Core\Managers\AnalyticsManager();
$summary = $analytics->getSummary();
$workload = $analytics->getWorkloadByCabinet();
$allAppointments = (new \Medical\Core\Managers\ScheduleManager())->getAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; mb-4">
    <h1>Расширенная аналитика и отчетность</h1>
    <div style="display: flex; gap: 10px;">
        <button class="btn" onclick="window.print()"><i data-lucide="printer" class="icon"></i> Печать</button>
        <a href="export.php?action=analytics_csv" class="btn btn-primary"><i data-lucide="download" class="icon"></i> Экспорт CSV</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 20px; margin-top: 20px;">
    <div class="card mica-effect">
        <h3 style="color: #666; font-size: 0.9rem;">Пациентов всего</h3>
        <div style="font-size: 2rem; font-weight: 600;"><?php echo $summary['total_patients']; ?></div>
    </div>
    <div class="card mica-effect">
        <h3 style="color: #666; font-size: 0.9rem;">Процент выполнения</h3>
        <div style="font-size: 2rem; font-weight: 600; color: #0078d4;">
            <?php echo $summary['total_appointments'] > 0 ? round(($summary['attended_count'] / $summary['total_appointments']) * 100) : 0; ?>%
        </div>
        <div style="font-size: 0.8rem; color: #666;"><?php echo $summary['attended_count']; ?> из <?php echo $summary['total_appointments']; ?></div>
    </div>
    <div class="card mica-effect">
        <h3 style="color: #666; font-size: 0.9rem;">Выручка (платные)</h3>
        <div style="font-size: 2rem; font-weight: 600; color: #107c10;"><?php echo number_format($summary['total_revenue'], 2, ',', ' '); ?> ₽</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <div class="card mica-effect">
        <h3>Загруженность кабинетов (Процедуры)</h3>
        <div style="display: flex; gap: 20px; align-items: flex-end; height: 250px; padding: 20px 40px;">
            <?php
            $max = max(1, count($allAppointments) > 0 ? max($workload) : 1);
            foreach ($workload as $cab => $count):
                $height = ($count / $max) * 100;
            ?>
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 10px;">
                    <div style="position: relative; width: 100%; background: #0078d4; border-radius: 4px 4px 0 0; height: <?php echo $height; ?>%; transition: height 0.5s;">
                        <span style="position: absolute; top: -25px; left: 50%; transform: translateX(-50%); font-weight: 600;"><?php echo $count; ?></span>
                    </div>
                    <span style="font-size: 0.8rem; color: #666;">Каб. <?php echo htmlspecialchars($cab); ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($workload)): ?>
                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #999;">Нет данных для отображения</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mica-effect">
        <h3>Нагрузка врачей</h3>
        <?php
        $doctorLoad = [];
        foreach ($allAppointments as $app) {
            $doc = $app['doctor'];
            $doctorLoad[$doc] = ($doctorLoad[$doc] ?? 0) + 1;
        }
        arsort($doctorLoad);
        foreach ($doctorLoad as $doc => $count):
            $percent = round(($count / count($allAppointments)) * 100);
        ?>
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 5px;">
                    <span><?php echo htmlspecialchars($doc); ?></span>
                    <strong><?php echo $count; ?></strong>
                </div>
                <div style="width: 100%; height: 8px; background: #eee; border-radius: 4px; overflow: hidden;">
                    <div style="width: <?php echo $percent; ?>%; height: 100%; background: #0078d4;"></div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($doctorLoad)): ?>
            <p style="color: #999;">Данных нет</p>
        <?php endif; ?>
    </div>
</div>

<div class="card mica-effect" style="margin-top: 20px;">
    <h3>Детализация по типам процедур</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid var(--win-border); text-align: left;">
                <th style="padding: 12px;">Наименование процедуры</th>
                <th style="padding: 12px;">Всего назначено</th>
                <th style="padding: 12px;">Выполнено</th>
                <th style="padding: 12px;">Выручка</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $procStats = [];
            foreach ($allAppointments as $app) {
                $pid = $app['procedure_id'];
                if (!isset($procStats[$pid])) {
                    $procStats[$pid] = ['name' => $app['procedure_name'], 'total' => 0, 'attended' => 0, 'revenue' => 0];
                }
                $procStats[$pid]['total']++;
                if ($app['attended']) $procStats[$pid]['attended']++;
                if ($app['status'] === 'paid') $procStats[$pid]['revenue'] += ($app['price'] ?? 0);
            }
            foreach ($procStats as $stat):
            ?>
                <tr style="border-bottom: 1px solid var(--win-border);">
                    <td style="padding: 12px; font-weight: 500;"><?php echo htmlspecialchars($stat['name']); ?></td>
                    <td style="padding: 12px;"><?php echo $stat['total']; ?></td>
                    <td style="padding: 12px;"><?php echo $stat['attended']; ?></td>
                    <td style="padding: 12px;"><?php echo number_format($stat['revenue'], 2, ',', ' '); ?> ₽</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
