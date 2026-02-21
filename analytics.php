<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\AnalyticsManager;

checkRole(['manager', 'admin']);

$requestStore = new JsonStore('data/requests.json');
$serviceStore = new JsonStore('data/services.json');
$userStore = new JsonStore('data/users.json');
$settingsStore = new JsonStore('data/settings.json');
$settings = $settingsStore->read();

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

$analytics = new AnalyticsManager($requestStore, $serviceStore, $settings);
$stats = $analytics->getStats($startDate, $endDate);
$bestPerformers = $analytics->getBestPerformers(3);

$services = [];
foreach ($serviceStore->read() as $s) $services[$s['id']] = $s['name'];

$users = [];
foreach ($userStore->read() as $u) $users[$u['id']] = $u['name'];

$statusNames = [
    'new' => 'Новые', 'assigned' => 'Назначены', 'working' => 'В работе',
    'checking' => 'На проверке', 'returned' => 'На доработке', 'completed' => 'Выполнены', 'closed' => 'Закрыты'
];

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-end; animation: slideDown 0.5s ease-out;">
        <div>
            <h1>Аналитическая панель</h1>
            <p style="color:var(--win-text-secondary);">Показатели эффективности и нагрузка служб</p>
        </div>
        <div style="display:flex; gap:12px;">
            <a href="export.php?<?php echo http_build_query($_GET); ?>" class="btn-primary" style="text-decoration:none; display:flex; align-items:center; gap:8px;">
                <i data-lucide="download"></i> Экспорт
            </a>
        </div>
    </div>

    <section class="card mica" style="animation: slideDown 0.4s ease-out; margin-bottom: 24px; padding: 16px;">
        <form method="GET" style="display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap;">
            <div class="form-group" style="margin:0; flex: 1; min-width: 150px;">
                <label style="font-size: 11px;">Дата С</label>
                <input type="date" name="start_date" value="<?php echo $startDate; ?>" style="height: 40px;">
            </div>
            <div class="form-group" style="margin:0; flex: 1; min-width: 150px;">
                <label style="font-size: 11px;">Дата По</label>
                <input type="date" name="end_date" value="<?php echo $endDate; ?>" style="height: 40px;">
            </div>
            <button type="submit" class="btn-primary" style="height: 40px; padding: 0 20px;">
                Применить
            </button>
            <?php if ($startDate || $endDate): ?>
                <a href="analytics.php" class="btn-secondary" style="height: 40px; text-decoration: none; display: flex; align-items: center; justify-content: center; padding: 0 16px;">
                    Сбросить
                </a>
            <?php endif; ?>
        </form>
    </section>

    <div class="card mica" style="margin-top: 24px; animation: slideUp 0.5s ease-out; border: none; background: linear-gradient(135deg, rgba(0, 120, 212, 0.1) 0%, rgba(142, 36, 170, 0.1) 100%);">
        <h2 style="margin-top:0; font-size:20px; margin-bottom:24px; display:flex; align-items:center; gap:8px;">
            <i data-lucide="trophy" style="color:#ffae00;"></i> Лучшие сотрудники
        </h2>
        <div class="leaderboard-grid">
            <?php
            $medals = ['#ffd700', '#c0c0c0', '#cd7f32'];
            foreach ($bestPerformers as $idx => $perf):
            ?>
                <div class="leader-card">
                    <div class="leader-medal" style="background: <?php echo $medals[$idx] ?? '#0078d4'; ?>;">
                        <?php echo $idx + 1; ?>
                    </div>
                    <div class="leader-info">
                        <div class="leader-name"><?php echo htmlspecialchars($perf['name']); ?></div>
                        <div class="leader-stats">
                            <span><i data-lucide="check-circle-2" style="width:12px; height:12px;"></i> <?php echo $perf['completed']; ?></span>
                            <span><i data-lucide="star" style="width:12px; height:12px; fill:#ffae00; color:#ffae00;"></i> <?php echo $perf['avg_rating']; ?></span>
                        </div>
                    </div>
                    <div class="leader-score">
                        <div style="font-size:10px; opacity:0.6; font-weight:700;">БАЛЛЫ</div>
                        <?php echo $perf['score']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="stats-grid" style="animation: slideUp 0.6s ease-out; margin-top: 24px;">
        <div class="stat-card mica colorful-card" style="--card-color: #0078d4;">
            <div class="stat-icon-new">
                <i data-lucide="layers"></i>
            </div>
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Всего заявок</div>
        </div>
        <div class="stat-card mica colorful-card" style="--card-color: #e81123;">
            <div class="stat-icon-new">
                <i data-lucide="alert-triangle"></i>
            </div>
            <div class="stat-value"><?php echo $stats['overdue']; ?></div>
            <div class="stat-label">Просрочено SLA</div>
        </div>
        <div class="stat-card mica colorful-card" style="--card-color: #107c10;">
            <div class="stat-icon-new">
                <i data-lucide="check-circle"></i>
            </div>
            <div class="stat-value"><?php echo $stats['completed_count']; ?></div>
            <div class="stat-label">Выполнено</div>
        </div>
        <div class="stat-card mica colorful-card" style="--card-color: #8e24aa;">
            <div class="stat-icon-new">
                <i data-lucide="clock"></i>
            </div>
            <div class="stat-value"><?php echo $stats['avg_hours']; ?><small style="font-size: 14px; margin-left: 2px;">ч</small></div>
            <div class="stat-label">Ср. время</div>
        </div>
    </div>

    <div class="form-grid" style="margin-top: 24px; animation: slideUp 0.7s ease-out;">
        <section class="card mica">
            <h2 style="margin-top:0; font-size:18px; margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                <i data-lucide="pie-chart" style="color:var(--win-accent);"></i> Статусы заявок
            </h2>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <?php foreach ($statusNames as $code => $name):
                    $count = $stats['by_status'][$code] ?? 0;
                    $percent = $stats['total'] > 0 ? round(($count / $stats['total']) * 100) : 0;
                    $link = "index.php?status=$code" . ($startDate ? "&start_date=$startDate" : "") . ($endDate ? "&end_date=$endDate" : "");
                ?>
                    <a href="<?php echo $link; ?>" style="text-decoration:none; color:inherit; display:block; margin-bottom: 12px;" class="clickable-stat">
                        <div style="display:flex; justify-content:space-between; font-size: 13px; margin-bottom: 4px;">
                            <span style="font-weight: 500;"><?php echo $name; ?></span>
                            <span style="font-weight: 700;"><?php echo $count; ?></span>
                        </div>
                        <div style="height: 6px; background: rgba(0,0,0,0.05); border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; width: <?php echo $percent; ?>%; background: var(--status-<?php echo $code; ?>);"></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card mica">
            <h2 style="margin-top:0; font-size:18px; margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                <i data-lucide="bar-chart-3" style="color:var(--win-accent);"></i> Нагрузка на службы
            </h2>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($services as $id => $name):
                    $count = $stats['by_service'][$id] ?? 0;
                    $percent = $stats['total'] > 0 ? round(($count / $stats['total']) * 100) : 0;
                ?>
                    <div style="display:flex; align-items:center; gap:12px; padding: 10px; background: rgba(0,0,0,0.02); border-radius: 8px; border: 1px solid var(--win-border);">
                        <div style="flex: 1; font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($name); ?></div>
                        <div style="font-weight: 800; font-size: 16px; color: var(--win-accent);"><?php echo $count; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="card mica" style="margin-top: 24px; animation: slideUp 0.8s ease-out;">
        <h2 style="margin-top:0; font-size:18px; margin-bottom:20px; display:flex; align-items:center; gap:8px;">
            <i data-lucide="users" style="color:var(--win-accent);"></i> Эффективность персонала
        </h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Сотрудник</th>
                        <th style="text-align:center;">Всего задач</th>
                        <th style="text-align:center;">Выполнено</th>
                        <th style="text-align:center;">КПД</th>
                        <th style="text-align:right;">Ср. время (ч)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    uasort($stats['by_performer'], function($a, $b) { return $b['completed'] <=> $a['completed']; });
                    foreach ($stats['by_performer'] as $pid => $pstats):
                        $efficiency = $pstats['total'] > 0 ? round(($pstats['completed'] / $pstats['total']) * 100) : 0;
                        $avgTime = $pstats['completed'] > 0 ? round($pstats['total_hours'] / $pstats['completed'], 1) : 0;
                        $link = "index.php?performer_id=$pid" . ($startDate ? "&start_date=$startDate" : "") . ($endDate ? "&end_date=$endDate" : "");
                    ?>
                        <tr onclick="window.location='<?php echo $link; ?>'" style="cursor:pointer;" class="table-hover-row">
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($users[$pid] ?? "ID: $pid"); ?></td>
                            <td style="text-align:center;"><?php echo $pstats['total']; ?></td>
                            <td style="text-align:center;"><?php echo $pstats['completed']; ?></td>
                            <td style="text-align:center;">
                                <span class="badge" style="background: rgba(0, 120, 212, 0.1); color: var(--win-accent);">
                                    <?php echo $efficiency; ?>%
                                </span>
                            </td>
                            <td style="text-align:right; font-weight: 700; color: var(--win-accent);"><?php echo $avgTime; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <section class="card mica" style="margin-top: 24px; animation: slideUp 0.9s ease-out; padding: 40px; text-align: center;">
        <h2 style="margin-top:0;">Краткий отчет по эффективности</h2>
        <div style="display: flex; justify-content: center; gap: 48px; margin-top: 32px;">
             <div>
                <div style="font-size: 48px; font-weight: 800; color: var(--win-accent); letter-spacing: -2px;">
                    <?php echo $stats['avg_hours']; ?>
                </div>
                <div style="font-size: 13px; font-weight: 600; color: var(--win-text-secondary); text-transform: uppercase;">Среднее время (часы)</div>
             </div>
             <div style="width: 1px; background: var(--win-border);"></div>
             <div>
                <div style="font-size: 48px; font-weight: 800; color: var(--status-completed); letter-spacing: -2px;">
                    <?php
                        $efficiency = $stats['total'] > 0 ? round(($stats['completed_count'] / $stats['total']) * 100) : 0;
                        echo $efficiency;
                    ?><small style="font-size: 24px;">%</small>
                </div>
                <div style="font-size: 13px; font-weight: 600; color: var(--win-text-secondary); text-transform: uppercase;">Процент выполнения</div>
             </div>
        </div>
    </section>
</div>

<style>
.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
}
.stat-icon i { width: 20px; height: 20px; }
.clickable-stat:hover { opacity: 0.7; }
.table-hover-row:hover { background: rgba(0,0,0,0.02); }

.leaderboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}
.leader-card {
    background: rgba(255,255,255,0.7);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 16px;
    border: 1px solid rgba(255,255,255,0.4);
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    transition: transform 0.2s;
}
.leader-card:hover { transform: scale(1.02); }
.leader-medal {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 800;
    font-size: 16px;
    flex-shrink: 0;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
.leader-info { flex: 1; }
.leader-name { font-weight: 700; font-size: 15px; margin-bottom: 4px; }
.leader-stats { display: flex; gap: 12px; font-size: 12px; color: var(--win-text-secondary); font-weight: 600; }
.leader-stats span { display: flex; align-items: center; gap: 4px; }
.leader-score { text-align: right; font-weight: 800; font-size: 18px; color: var(--win-accent); }

.colorful-card {
    position: relative;
    overflow: hidden;
    color: white;
    background: linear-gradient(135deg, var(--card-color) 0%, rgba(0,0,0,0.8) 150%) !important;
    border: none !important;
}
.colorful-card .stat-value { color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.2); }
.colorful-card .stat-label { color: rgba(255,255,255,0.8); }
.stat-icon-new {
    position: absolute;
    top: 10px;
    right: 10px;
    opacity: 0.2;
    transform: scale(2.5);
    pointer-events: none;
}
</style>

<?php include 'includes/footer.php'; ?>
