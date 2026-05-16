<?php
require_once __DIR__ .../../../s../autoload.php';
use App\Database\JsonStore;
use App\Analytics\AnalyticsManager;

$store = new JsonStore(__DIR__ .../../data');
$analytics = new AnalyticsManager($store);
$stats = $analytics->getStats();

$pageTitle = 'Дашборд';
include 'includ../header.php';
?>

<div class="m-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="margin:0;">📊 Статистик../h2>
        <span style="font-size: 0.7rem; color: #64748b;"><?php echo date('d.m.Y H:i'); ?../span>
   ../div>
    <div class="m-stats-grid">
        <div class="m-stat-item" style="grid-column: span 2; background: linear-gradient(135deg, #0078d4, #005a9e); color: white;">
            <div class="m-stat-value" style="color: white;"><?php echo number_format($stats['totalIncome'] ?? 0, 0, '.', ' '); ?../div>
            <div class="m-stat-label" style="color: rgba(255,255,255,0.8);">Общий доход (₽../div>
       ../div>
        <div class="m-stat-item">
            <div class="m-stat-value"><?php echo $stats['occupancyRate'] ?? 0; ?>../div>
            <div class="m-stat-label">Загрузк../div>
       ../div>
        <div class="m-stat-item">
            <div class="m-stat-value"><?php echo $stats['totalBookings'] ?? 0; ?../div>
            <div class="m-stat-label">Всего броне../div>
       ../div>
   ../div>
</div>

<div class="m-card">
    <h2>🚀 Быстрые действи../h2>
    <div style="display: flex; flex-direction: column; gap: 10px;">
        <a href="create_booking.php" class="btn-m" style="background: #10b981; color: white;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><line x1="12" y1="5" x2="12" y2="19"../line><line x1="5" y1="12" x2="19" y2="12"../line../svg>
            Создать бронирование
       ../a>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <a href="calendar.php" class="btn-m" style="background: #f1f5f9; color: #1e293b;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M3 3h18v18H3zM3 9h18M9 3v18"../path../svg>
                Шахматка
           ../a>
            <a href="tasks.php" class="btn-m" style="background: #f1f5f9; color: #1e293b;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M9 11l3 3L22 4"../path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"../path../svg>
                Задачи
           ../a>
       ../div>
        <a href="../hourly_grid.php" class="btn-m" style="background: #6264a7; color: white;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M12 2v20"../path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"../path../svg>
            Почасовая сетка (Админ)
       ../a>
   ../div>
</div>

<div class="m-card">
    <h2>📅 Сегодн../h2>
    <?php
        $today = date('Y-m-d');
        $bookings = $store->findAll('bookings');
        $arrivals = 0; $departures = 0;
        foreach($bookings as $b) {
            if (strpos($b['check_in'] ?? '', $today) === 0 && ($b['status'] ?? '') !== 'cancelled') $arrivals++;
            if (strpos($b['check_out'] ?? '', $today) === 0 && ($b['status'] ?? '') !== 'cancelled') $departures++;
        }
        $allPlans = $store->findAll('plans');
        $tasksToday = count(array_filter($allPlans, function($p) use ($today) { return ($p['date'] ?? '') === $today; }));
        $tasksDone = count(array_filter($allPlans, function($p) use ($today) { return ($p['date'] ?? '') === $today && ($p['status'] ?? '') === 'completed'; }));
    ?>
    <div style="display: flex; flex-direction: column; gap: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f1f5f9;">
            <span style="font-size: 0.9rem; color: #64748b;">Заездов сегодн../span>
            <span style="font-weight: 700; color: #1e293b;"><?php echo $arrivals; ?../span>
       ../div>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f1f5f9;">
            <span style="font-size: 0.9rem; color: #64748b;">Выездов сегодн../span>
            <span style="font-weight: 700; color: #1e293b;"><?php echo $departures; ?../span>
       ../div>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
            <span style="font-size: 0.9rem; color: #64748b;">Задач выполнен../span>
            <span style="font-weight: 700; color: #1e293b;"><?php echo $tasksDone; ?../ <?php echo $tasksToday; ?../span>
       ../div>
   ../div>
</div>

<?php include 'includ../footer.php'; ?>
