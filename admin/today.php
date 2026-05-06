<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Booking\BookingManager;
use Sanatorium\Core\Planning\PlanningManager;

$store = new JsonStore(__DIR__ . '/../data');
$bookingManager = new BookingManager($store);
$planManager = new PlanningManager($store);

$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'check_in') {
        $bookingManager->updateBookingStatus((int)$_POST['id'], 'booked');
    } elseif ($_POST['action'] === 'check_out') {
        $bookingManager->updateBookingStatus((int)$_POST['id'], 'confirmed');
    } elseif ($_POST['action'] === 'complete_plan') {
        $plan = $store->findOne('plans', (int)$_POST['id']);
        if ($plan) {
            $plan['status'] = 'completed';
            $store->save('plans', $plan);
        }
    }
    header('Location: today.php');
    exit;
}

$bookings = $store->findAll('bookings');
if (!is_array($bookings)) $bookings = [];

$arrivals = array_filter($bookings, function($b) use ($today) {
    return substr($b['check_in'] ?? '', 0, 10) === $today && ($b['status'] ?? '') !== 'cancelled' && ($b['status'] ?? '') !== 'booked';
});

$departures = array_filter($bookings, function($b) use ($today) {
    return substr($b['check_out'] ?? '', 0, 10) === $today && ($b['status'] ?? '') !== 'cancelled';
});

$staying = array_filter($bookings, function($b) use ($today) {
    return substr($b['check_in'] ?? '', 0, 10) <= $today && substr($b['check_out'] ?? '', 0, 10) > $today && ($b['status'] ?? '') === 'booked';
});

$totalRevenueToday = 0;
foreach($staying as $b) {
    $totalPrice = (float)($b['total_price'] ?? 0);
    $checkIn = strtotime($b['check_in']);
    $checkOut = strtotime($b['check_out']);
    $days = max(1, ($checkOut - $checkIn) / 86400);
    $totalRevenueToday += $totalPrice / $days;
}

$plansToday = $planManager->getByDate($today);

$rooms = $store->findAll('rooms');
$roomMap = [];
if (is_array($rooms)) {
    foreach ($rooms as $r) {
        if (is_array($r) && isset($r['id'])) $roomMap[$r['id']] = $r['room_number'];
    }
}

$allPackages = $store->findAll('packages');
$packageMap = [];
if (is_array($allPackages)) foreach ($allPackages as $p) if(isset($p['id'])) $packageMap[$p['id']] = $p['name'];

$allProcedures = $store->findAll('procedures');
$procedureMap = [];
if (is_array($allProcedures)) foreach ($allProcedures as $p) if(isset($p['id'])) $procedureMap[$p['id']] = $p['name'];

$allServices = $store->findAll('extra_services');
$serviceMap = [];
if (is_array($allServices)) foreach ($allServices as $s) if(isset($s['id'])) $serviceMap[$s['id']] = $s['name'];

$pageTitle = 'Сегодня в санатории';
include 'includes/header.php';
?>

<div class="grid-4" style="margin-bottom: 24px;">
    <div class="mica-card stat-card" style="margin-bottom:0;">
        <h3>Ожидается заездов</h3>
        <h2><?php echo count($arrivals); ?></h2>
    </div>
    <div class="mica-card stat-card" style="margin-bottom:0;">
        <h3>Ожидается выездов</h3>
        <h2><?php echo count($departures); ?></h2>
    </div>
    <div class="mica-card stat-card" style="margin-bottom:0; background: rgba(0, 120, 212, 0.1);">
        <h3>Сейчас в санатории</h3>
        <h2><?php echo count($staying); ?></h2>
    </div>
    <div class="mica-card stat-card" style="margin-bottom:0; border: 1px solid #d1e7dd;">
        <h3>Расч. доход за сегодня</h3>
        <h2><?php echo number_format($totalRevenueToday, 0, ',', ' '); ?> ₽</h2>
    </div>
</div>

<div class="mica-card" style="margin-bottom: 24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px;">
        <h3 style="margin:0; font-size: 0.95rem;">📊 Общая загрузка на сегодня</h3>
        <?php
            $totalRooms = count($rooms) ?: 1;
            $occPercent = round((count($staying) / $totalRooms) * 100);
        ?>
        <span style="font-weight:600;"><?php echo $occPercent; ?>%</span>
    </div>
    <div style="height: 12px; background: rgba(0,0,0,0.05); border-radius: 6px; overflow: hidden; border: 1px solid rgba(0,0,0,0.02);">
        <div style="height: 100%; background: linear-gradient(90deg, #0078d4, #2b88d8); width: <?php echo $occPercent; ?>%; transition: width 0.5s;"></div>
    </div>
    <div style="margin-top: 8px; font-size: 0.8rem; color: #666; display:flex; justify-content:space-between;">
        <span>Свободно: <?php echo $totalRooms - count($staying); ?></span>
        <span>Всего номеров: <?php echo $totalRooms; ?></span>
    </div>
</div>

<div class="grid-2">
    <!-- Arrivals -->
    <div class="mica-card">
        <h3>🧳 Заезды сегодня (<?php echo count($arrivals); ?>)</h3>
        <?php if(empty($arrivals)): ?>
            <p style="color:#888; padding: 20px 0;">Заездов не запланировано</p>
        <?php else: ?>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach($arrivals as $b): ?>
                    <div style="padding: 15px; border-bottom: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong style="font-size: 1.1rem;"><?php echo htmlspecialchars($b['client_name'] ?? ''); ?></strong><br>
                            <span style="color:#666; font-size: 0.9rem;">
                                Номер: <strong><?php echo htmlspecialchars($roomMap[$b['room_id'] ?? 0] ?? 'N/A'); ?></strong> |
                                Тел: <?php echo htmlspecialchars($b['phone'] ?? ''); ?>
                            </span><br>
                            <small>Период: <?php echo $b['check_in']; ?> — <?php echo $b['check_out']; ?></small><br>
                            <?php if(!empty($b['package_id'])): ?>
                                <small style="color:var(--primary-color);">Пакет: <?php echo htmlspecialchars($packageMap[$b['package_id']] ?? 'ID '.$b['package_id']); ?></small><br>
                            <?php endif; ?>
                            <?php
                                $extras = [];
                                if(!empty($b['procedure_ids'])) foreach($b['procedure_ids'] as $pid) if(isset($procedureMap[$pid])) $extras[] = $procedureMap[$pid];
                                if(!empty($b['service_ids'])) foreach($b['service_ids'] as $sid) if(isset($serviceMap[$sid])) $extras[] = $serviceMap[$sid];
                                if(!empty($extras)):
                            ?>
                                <small style="color:#666;">Доп: <?php echo htmlspecialchars(implode(', ', $extras)); ?></small>
                            <?php endif; ?>
                        </div>
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="action" value="check_in">
                            <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                            <button type="submit" class="btn">Заселить гостя</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Plans -->
    <div class="mica-card">
        <h3>📅 Планы на сегодня (<?php echo count($plansToday); ?>)</h3>
        <?php if(empty($plansToday)): ?>
            <p style="color:#888; padding: 20px 0;">На сегодня планов нет</p>
        <?php else: ?>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach($plansToday as $p): ?>
                    <div style="padding: 15px; border-bottom: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; <?php echo ($p['status'] ?? '') === 'completed' ? 'opacity: 0.5;' : ''; ?>">
                        <div>
                            <strong style="<?php echo ($p['status'] ?? '') === 'completed' ? 'text-decoration: line-through;' : ''; ?>"><?php echo htmlspecialchars($p['title'] ?? ''); ?></strong>
                            <?php if(!empty($p['description'])): ?>
                                <br><small style="color:#666;"><?php echo htmlspecialchars($p['description']); ?></small>
                            <?php endif; ?>
                            <br><span style="font-size: 0.75rem; color: #0078d4; font-weight: bold;"><?php echo strtoupper($p['priority'] ?? 'medium'); ?></span>
                        </div>
                        <?php if(($p['status'] ?? '') !== 'completed'): ?>
                            <form method="post" style="margin:0;">
                                <input type="hidden" name="action" value="complete_plan">
                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                <button type="submit" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.8rem;">Завершить</button>
                            </form>
                        <?php else: ?>
                            <span style="color: green; font-weight: bold;">✓</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div style="margin-top: 15px; text-align: center;">
            <a href="planning.php" style="font-size: 0.9rem; color: var(--primary-color); text-decoration: none;">Перейти к планированию →</a>
        </div>
    </div>
</div>

<div class="grid-2">
    <div class="mica-card">
        <h3>🚪 Выезды сегодня (<?php echo count($departures); ?>)</h3>
        <?php if(empty($departures)): ?>
            <p style="color:#888; padding: 20px 0;">Выездов не запланировано</p>
        <?php else: ?>
            <div class="table-responsive">
            <table style="font-size: 0.9rem;">
                <thead>
                    <tr>
                        <th>Гость</th>
                        <th>Номер</th>
                        <th>Статус</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($departures as $b): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($b['client_name'] ?? ''); ?></strong></td>
                            <td><?php echo htmlspecialchars($roomMap[$b['room_id'] ?? 0] ?? 'N/A'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $b['status'] ?? 'new'; ?>" style="font-size:0.7rem;">
                                    <?php echo $b['status'] === 'booked' ? 'В номере' : ($b['status'] === 'confirmed' ? 'Подтверждено' : $b['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($b['status'] === 'booked'): ?>
                                <form method="post" style="margin:0;">
                                    <input type="hidden" name="action" value="check_out">
                                    <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding: 2px 8px; font-size: 0.75rem;">Выселить</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="mica-card">
        <h3>🧹 Требуется уборка</h3>
        <?php if(empty($departures)): ?>
            <p style="color:#888; padding: 20px 0;">Уборка не требуется (выездов нет)</p>
        <?php else: ?>
            <ul style="list-style:none; padding:0;">
                <?php foreach($departures as $b): ?>
                    <li style="padding: 10px; background: rgba(0,0,0,0.02); border-radius: 6px; margin-bottom: 8px; display:flex; justify-content:space-between; align-items:center;">
                        <span>Номер <strong><?php echo htmlspecialchars($roomMap[$b['room_id'] ?? 0] ?? 'N/A'); ?></strong></span>
                        <span style="font-size: 0.8rem; background: #fff3cd; padding: 2px 8px; border-radius: 4px;">После выезда</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
