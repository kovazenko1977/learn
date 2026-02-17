<?php
require_once __DIR__ . '/includes/header.php';
\Medical\Core\Auth::requireLogin();

$currentUser = \Medical\Core\Auth::getUser();

if (!\Medical\Core\Auth::hasRole('admin')) {
    echo '<div class="card mica-effect"><h2>Доступ ограничен</h2><p>Только администратор может просматривать этот раздел.</p></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$staffManager = new \Medical\Core\Managers\StaffManager();
$procedureManager = new \Medical\Core\Managers\ProcedureManager();

// Handle Actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        die('CSRF validation failed');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_staff') {
        $staffManager->create([
            'name' => $_POST['name'],
            'role' => $_POST['role'],
            'specialization' => $_POST['specialization']
        ]);
        $message = 'Сотрудник добавлен';
    } elseif ($action === 'delete_staff') {
        $staffManager->delete($_POST['id']);
        $message = 'Сотрудник удален';
    } elseif ($action === 'add_procedure') {
        $procedureManager->add([
            'name' => $_POST['name'],
            'duration' => (int)$_POST['duration'],
            'prep_time' => (int)$_POST['prep_time'],
            'price' => (float)$_POST['price'],
            'is_paid' => isset($_POST['is_paid']),
            'assigned_staff' => $_POST['assigned_staff'] ?? []
        ]);
        $message = 'Процедура добавлена';
    } elseif ($action === 'delete_procedure') {
        $procedureManager->delete($_POST['id']);
        $message = 'Процедура удалена';
    }
}

$activeSub = $_GET['sub'] ?? 'procedures';
$allStaff = $staffManager->getAll();
$allProcedures = $procedureManager->getAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Настройки и Справочники</h1>
    <div style="display: flex; gap: 10px;">
        <a href="?sub=procedures" class="btn <?php echo $activeSub === 'procedures' ? 'btn-primary' : ''; ?>">Процедуры</a>
        <a href="?sub=staff" class="btn <?php echo $activeSub === 'staff' ? 'btn-primary' : ''; ?>">Персонал</a>
    </div>
</div>

<?php if ($message): ?>
    <div style="background: #dff6dd; color: #107c10; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #107c10;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php if ($activeSub === 'procedures'): ?>
    <div class="card mica-effect mb-4">
        <h2>Добавить процедуру</h2>
        <form method="POST" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="add_procedure">

            <div>
                <label>Наименование</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div>
                <label>Длит. (мин)</label>
                <input type="number" name="duration" class="form-control" value="20" required>
            </div>
            <div>
                <label>Подг. (мин)</label>
                <input type="number" name="prep_time" class="form-control" value="5" required>
            </div>
            <div>
                <label>Цена (руб)</label>
                <input type="number" step="0.01" name="price" class="form-control" value="0" required>
            </div>
            <div style="display: flex; align-items: center; gap: 5px; padding-bottom: 10px;">
                <input type="checkbox" name="is_paid" id="is_paid" checked>
                <label for="is_paid">Платная</label>
            </div>

            <div style="grid-column: span 4;">
                <label>Закрепленные сотрудники</label>
                <div style="display: flex; flex-wrap: wrap; gap: 10px; padding: 10px; border: 1px solid var(--win-border); border-radius: 4px; background: rgba(255,255,255,0.3);">
                    <?php foreach ($allStaff as $s): ?>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" name="assigned_staff[]" value="<?php echo $s['id']; ?>">
                            <?php echo htmlspecialchars($s['name']); ?> (<?php echo $s['role']; ?>)
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="height: 40px;">Добавить</button>
        </form>
    </div>

    <div class="card mica-effect">
        <h2>Список процедур</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--win-border); text-align: left;">
                    <th style="padding: 10px;">Название</th>
                    <th style="padding: 10px;">Время (Д+П)</th>
                    <th style="padding: 10px;">Цена</th>
                    <th style="padding: 10px;">Персонал</th>
                    <th style="padding: 10px; text-align: right;">Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allProcedures as $p): ?>
                    <tr style="border-bottom: 1px solid var(--win-border);">
                        <td style="padding: 10px; font-weight: 500;"><?php echo htmlspecialchars($p['name']); ?></td>
                        <td style="padding: 10px;"><?php echo $p['duration']; ?> + <?php echo $p['prep_time'] ?? 0; ?> мин</td>
                        <td style="padding: 10px;">
                            <?php echo number_format($p['price'], 2, ',', ' '); ?> ₽
                            <?php if (!($p['is_paid'] ?? false)): ?>
                                <span style="font-size: 0.8rem; background: #eee; padding: 2px 6px; border-radius: 4px;">Бесплатно</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px;">
                            <?php
                            $assignedIds = $p['assigned_staff'] ?? [];
                            foreach ($assignedIds as $sid) {
                                $s = $staffManager->getById($sid);
                                if ($s) {
                                    echo '<span style="font-size: 0.8rem; background: #e1f0fe; color: #0078d4; padding: 2px 6px; border-radius: 4px; margin-right: 5px;">' . htmlspecialchars($s['name']) . '</span>';
                                }
                            }
                            ?>
                        </td>
                        <td style="padding: 10px; text-align: right;">
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить процедуру?')">
                                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                <input type="hidden" name="action" value="delete_procedure">
                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                <button type="submit" style="background: none; border: none; color: #d13438; cursor: pointer;"><i data-lucide="trash-2" class="icon"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($activeSub === 'staff'): ?>
    <div class="card mica-effect mb-4">
        <h2>Добавить сотрудника</h2>
        <form method="POST" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end;">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="add_staff">

            <div>
                <label>ФИО</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div>
                <label>Роль</label>
                <select name="role" class="form-control">
                    <option value="doctor">Врач</option>
                    <option value="nurse">Медсестра</option>
                    <option value="specialist">Специалист</option>
                </select>
            </div>
            <div>
                <label>Специализация</label>
                <input type="text" name="specialization" class="form-control" placeholder="например, Терапевт">
            </div>

            <button type="submit" class="btn btn-primary" style="height: 40px;">Добавить</button>
        </form>
    </div>

    <div class="card mica-effect">
        <h2>Список персонала</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--win-border); text-align: left;">
                    <th style="padding: 10px;">ФИО</th>
                    <th style="padding: 10px;">Роль</th>
                    <th style="padding: 10px;">Специализация</th>
                    <th style="padding: 10px; text-align: right;">Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allStaff as $s): ?>
                    <tr style="border-bottom: 1px solid var(--win-border);">
                        <td style="padding: 10px; font-weight: 500;"><?php echo htmlspecialchars($s['name']); ?></td>
                        <td style="padding: 10px;"><?php echo $s['role']; ?></td>
                        <td style="padding: 10px;"><?php echo htmlspecialchars($s['specialization']); ?></td>
                        <td style="padding: 10px; text-align: right;">
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить сотрудника?')">
                                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                <input type="hidden" name="action" value="delete_staff">
                                <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                <button type="submit" style="background: none; border: none; color: #d13438; cursor: pointer;"><i data-lucide="trash-2" class="icon"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
