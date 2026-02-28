<?php
require_once __DIR__ . '/../Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('settings_system')) {
    die("У вас недостаточно прав.");
}

$staffManager = new \Medical\Core\Managers\StaffManager();
$procedureManager = new \Medical\Core\Managers\ProcedureManager();

$staff = $staffManager->getAll();
$procedures = $procedureManager->getAll();

include __DIR__ . '/includes/header.php';
?>

<div style="padding: 16px; background: #F3EDF7;">
    <h2 style="margin: 0; font-size: 20px; font-weight: 500;">Настройки системы</h2>
</div>

<div style="padding: 16px; padding-bottom: 100px;">
    <div style="display: flex; gap: 8px; margin-bottom: 24px;">
        <button onclick="switchTab('staff')" id="tab-btn-staff" style="flex: 1; padding: 12px; border: none; background: none; border-bottom: 2px solid var(--md-primary); color: var(--md-primary); font-weight: 500;">Персонал</button>
        <button onclick="switchTab('procedures')" id="tab-btn-procedures" style="flex: 1; padding: 12px; border: none; background: none; color: var(--md-secondary); font-weight: 500;">Процедуры</button>
    </div>

    <div id="settings-tab-staff">
        <h3 style="margin-top: 0; font-size: 16px; font-weight: 500; margin-bottom: 12px;">Список сотрудников</h3>
        <?php foreach ($staff as $s): ?>
            <div class="md-list-item" style="padding: 12px 0;">
                <div style="width: 40px; height: 40px; background: #E8DEF8; border-radius: 20px; display: flex; align-items: center; justify-content: center; color: var(--md-primary); font-weight: 700;">
                    <?php echo mb_substr($s['name'], 0, 1); ?>
                </div>
                <div style="flex-grow: 1;">
                    <div style="font-weight: 500;"><?php echo htmlspecialchars($s['name']); ?></div>
                    <div style="font-size: 13px; color: var(--md-secondary);"><?php echo htmlspecialchars($s['role']); ?> • Код: <strong><?php echo htmlspecialchars($s['access_code']); ?></strong></div>
                </div>
                <div style="font-size: 12px; color: var(--md-primary); background: #fef7ff; padding: 2px 8px; border-radius: 12px; border: 1px solid #CAC4D0;">
                    Каб. <?php echo htmlspecialchars($s['cabinet'] ?? '-'); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="settings-tab-procedures" style="display: none;">
        <h3 style="margin-top: 0; font-size: 16px; font-weight: 500; margin-bottom: 12px;">Справочник услуг</h3>
        <?php foreach ($procedures as $p): ?>
            <div class="md-list-item" style="padding: 12px 0; align-items: flex-start;">
                <div style="flex-grow: 1;">
                    <div style="font-weight: 500;"><?php echo htmlspecialchars($p['name']); ?></div>
                    <div style="font-size: 13px; color: var(--md-secondary); margin-top: 4px;">
                        <span><?php echo $p['type'] === 'free' ? 'Бесплатно' : number_format($p['price'], 2) . ' ₽'; ?></span>
                        <span> • </span>
                        <span><?php echo $p['duration']; ?> мин</span>
                    </div>
                </div>
                <div style="font-size: 12px; background: #F3EDF7; padding: 2px 8px; border-radius: 4px;">
                    Каб. <?php echo htmlspecialchars($p['default_cabinet'] ?? '-'); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="padding: 32px 16px; text-align: center; border-top: 1px solid #CAC4D0; margin-top: 24px;">
        <p style="font-size: 12px; color: var(--md-secondary); line-height: 1.4;">Управление ролями, создание учетных записей и расширенные настройки доступны в полной версии.</p>
        <a href="../settings.php" class="md-btn" style="background: #eee; border-radius: 8px; font-size: 13px; margin-top: 12px;">Открыть полную версию</a>
    </div>
</div>

<script>
function switchTab(tab) {
    document.getElementById('settings-tab-staff').style.display = tab === 'staff' ? 'block' : 'none';
    document.getElementById('settings-tab-procedures').style.display = tab === 'procedures' ? 'block' : 'none';

    document.getElementById('tab-btn-staff').style.color = tab === 'staff' ? 'var(--md-primary)' : 'var(--md-secondary)';
    document.getElementById('tab-btn-staff').style.borderBottom = tab === 'staff' ? '2px solid var(--md-primary)' : 'none';

    document.getElementById('tab-btn-procedures').style.color = tab === 'procedures' ? 'var(--md-primary)' : 'var(--md-secondary)';
    document.getElementById('tab-btn-procedures').style.borderBottom = tab === 'procedures' ? '2px solid var(--md-primary)' : 'none';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
