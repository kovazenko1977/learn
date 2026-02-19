<?php
require_once __DIR__ . '/../Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

$patientManager = new \Medical\Core\Managers\PatientManager();
$query = $_GET['q'] ?? '';
$doctorFilter = $_GET['doctor'] ?? '';

$patients = $query ? $patientManager->search($query) : $patientManager->getAll();

if ($doctorFilter) {
    $patients = array_filter($patients, function($p) use ($doctorFilter) {
        return ($p['treating_doctor'] ?? '') === $doctorFilter;
    });
}

include __DIR__ . '/includes/header.php';
?>

<div style="padding: 16px; position: sticky; top: 64px; background: var(--md-bg); z-index: 90;">
    <form method="GET" style="position: relative;">
        <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" class="md-input" placeholder="Поиск пациента..." style="margin-bottom: 0; padding-left: 48px;">
        <i data-lucide="search" style="position: absolute; left: 16px; top: 16px; color: var(--md-secondary);"></i>
    </form>
</div>

<div style="padding-bottom: 100px;">
    <?php foreach (array_slice($patients, 0, 50) as $p): ?>
        <div class="md-list-item" onclick="location.href='attendance.php?patient_id=<?php echo $p['id']; ?>'">
            <div style="width: 40px; height: 40px; background: #E8DEF8; border-radius: 20px; display: flex; align-items: center; justify-content: center; color: var(--md-primary); flex-shrink: 0;">
                <i data-lucide="user"></i>
            </div>
            <div style="flex-grow: 1;">
                <div style="font-weight: 500; font-size: 16px;"><?php echo htmlspecialchars($p['name']); ?></div>
                <div style="font-size: 14px; color: var(--md-secondary);"><?php echo date('d.m.Y', strtotime($p['birth_date'])); ?> • Карта: <?php echo htmlspecialchars($p['card_number'] ?? '-'); ?></div>
                <?php if (!empty($p['treating_doctor'])): ?>
                    <div style="font-size: 12px; color: var(--md-primary); margin-top: 2px;">Врач: <?php echo htmlspecialchars($p['treating_doctor']); ?></div>
                <?php endif; ?>
            </div>
            <i data-lucide="chevron-right" style="color: #CAC4D0;"></i>
        </div>
    <?php endforeach; ?>

    <?php if (empty($patients)): ?>
        <div style="text-align: center; padding: 48px; color: var(--md-secondary);">
            <i data-lucide="user-x" style="width: 48px; height: 48px; margin-bottom: 16px;"></i>
            <p>Пациенты не найдены</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
