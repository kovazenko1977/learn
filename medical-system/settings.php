<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

$currentUser = \Medical\Core\Auth::getUser();

if (!\Medical\Core\Auth::hasRole(['admin', 'chief'])) {
    echo '<div class="card mica-effect"><h2>Доступ ограничен</h2><p>Только администратор или начмед могут просматривать этот раздел.</p></div>';
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

    if ($action === 'add_staff' && \Medical\Core\Auth::canManageStaff()) {
        $staffManager->create([
            'name' => $_POST['name'],
            'role' => $_POST['role'],
            'specialization' => $_POST['specialization'],
            'access_code' => $_POST['access_code']
        ]);
        $message = 'Сотрудник добавлен';
    } elseif ($action === 'edit_staff' && \Medical\Core\Auth::canManageStaff()) {
        $staffManager->update($_POST['id'], [
            'name' => $_POST['name'],
            'role' => $_POST['role'],
            'specialization' => $_POST['specialization'],
            'access_code' => $_POST['access_code']
        ]);
        $message = 'Данные сотрудника обновлены';
    } elseif ($action === 'delete_staff' && \Medical\Core\Auth::canManageStaff()) {
        $staffManager->delete($_POST['id']);
        $message = 'Сотрудник удален';
    } elseif ($action === 'add_procedure') {
        $procedureManager->add([
            'name' => $_POST['name'],
            'duration' => (int)$_POST['duration'],
            'prep_time' => (int)$_POST['prep_time'],
            'work_start' => $_POST['work_start'] ?? '08:00',
            'work_end' => $_POST['work_end'] ?? '17:00',
            'price' => (float)$_POST['price'],
            'is_paid' => isset($_POST['is_paid']),
            'default_cabinet' => $_POST['default_cabinet'] ?? '',
            'assigned_staff' => $_POST['assigned_staff'] ?? []
        ]);
        $message = 'Процедура добавлена';
    } elseif ($action === 'edit_procedure') {
        $procedureManager->update($_POST['id'], [
            'name' => $_POST['name'],
            'duration' => (int)$_POST['duration'],
            'prep_time' => (int)$_POST['prep_time'],
            'work_start' => $_POST['work_start'] ?? '08:00',
            'work_end' => $_POST['work_end'] ?? '17:00',
            'price' => (float)$_POST['price'],
            'is_paid' => isset($_POST['is_paid']),
            'default_cabinet' => $_POST['default_cabinet'] ?? '',
            'assigned_staff' => $_POST['assigned_staff'] ?? []
        ]);
        $message = 'Процедура обновлена';
    } elseif ($action === 'save_ui_settings') {
        $settingsStore = new \Medical\Core\JsonStore('settings');
        $existing = $settingsStore->getAll();
        $uiSettings = array_merge($existing, [
            'font_family' => $_POST['font_family'],
            'font_size' => (int)$_POST['font_size'],
            'accent_color' => $_POST['accent_color'],
            'border_radius' => (int)$_POST['border_radius']
        ]);
        $settingsStore->save($uiSettings);
        $message = 'Настройки внешнего вида сохранены';
    } elseif ($action === 'save_sanatorium_details') {
        $settingsStore = new \Medical\Core\JsonStore('settings');
        $existing = $settingsStore->getAll();
        $details = array_merge($existing, [
            'org_name' => $_POST['org_name'],
            'org_address' => $_POST['org_address'],
            'org_phone' => $_POST['org_phone'],
            'org_unp' => $_POST['org_unp'],
            'org_bank' => $_POST['org_bank'],
            'org_account' => $_POST['org_account'],
            'org_director' => $_POST['org_director']
        ]);
        $settingsStore->save($details);
        $message = 'Реквизиты организации сохранены';
    } elseif ($action === 'delete_procedure') {
        $procedureManager->delete($_POST['id']);
        $message = 'Процедура удалена';
    } elseif ($action === 'backup_system') {
        $backupManager = new \Medical\Core\Managers\BackupManager();
        $file = $backupManager->createBackup();
        if ($file) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            unlink($file); // Delete temporary zip after download
            exit;
        } else {
            $message = 'Ошибка при создании резервной копии';
        }
    } elseif ($action === 'restore_system') {
        if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
            $backupManager = new \Medical\Core\Managers\BackupManager();
            if ($backupManager->restoreBackup($_FILES['backup_file']['tmp_name'])) {
                $message = 'Система успешно восстановлена из резервной копии';
            } else {
                $message = 'Ошибка при восстановлении данных';
            }
        }
    } elseif ($action === 'reset_system') {
        $backupManager = new \Medical\Core\Managers\BackupManager();
        if ($backupManager->resetSystem($_POST['reset_password'])) {
            $message = 'Система успешно сброшена. Все данные удалены.';
            (new \Medical\Core\Managers\LogManager())->log('Сброс системы', []);
        } else {
            $message = 'Ошибка: неверный пароль для сброса данных';
        }
    } elseif ($action === 'save_db_settings') {
        $settingsStore = new \Medical\Core\JsonStore('settings');
        $existing = $settingsStore->getAll();
        $dbSettings = array_merge($existing, [
            'db_driver' => $_POST['db_driver'],
            'db_host' => $_POST['db_host'],
            'db_name' => $_POST['db_name'],
            'db_user' => $_POST['db_user'],
            'db_pass' => $_POST['db_pass']
        ]);
        $settingsStore->save($dbSettings);
        $message = 'Настройки базы данных сохранены. ' . ($_POST['db_driver'] === 'mysql' ? 'Переключено на MySQL.' : 'Используется JSON.');
    } elseif ($action === 'init_mysql') {
        try {
            if (\Medical\Core\DB::initTables()) {
                $message = 'Таблицы MySQL успешно созданы/проверены.';
            }
        } catch (\Exception $e) {
            $message = 'Ошибка инициализации MySQL: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';

$activeSub = $_GET['sub'] ?? 'procedures';
$allStaff = $staffManager->getAll();
$allProcedures = $procedureManager->getAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Настройки и Справочники</h1>
    <div style="display: flex; gap: 10px;">
        <a href="?sub=procedures" class="btn <?php echo $activeSub === 'procedures' ? 'btn-primary' : ''; ?>">Процедуры</a>
        <?php if (\Medical\Core\Auth::canManageStaff()): ?>
            <a href="?sub=staff" class="btn <?php echo $activeSub === 'staff' ? 'btn-primary' : ''; ?>">Персонал</a>
        <?php endif; ?>
        <a href="?sub=details" class="btn <?php echo $activeSub === 'details' ? 'btn-primary' : ''; ?>">Реквизиты</a>
        <a href="?sub=database" class="btn <?php echo $activeSub === 'database' ? 'btn-primary' : ''; ?>">База данных</a>
        <a href="?sub=appearance" class="btn <?php echo $activeSub === 'appearance' ? 'btn-primary' : ''; ?>">Внешний вид</a>
        <?php if (\Medical\Core\Auth::hasRole('chief')): ?>
            <a href="?sub=logs" class="btn <?php echo $activeSub === 'logs' ? 'btn-primary' : ''; ?>">Логи</a>
        <?php endif; ?>
        <?php if (\Medical\Core\Auth::isAdmin()): ?>
            <a href="?sub=maintenance" class="btn <?php echo $activeSub === 'maintenance' ? 'btn-primary' : ''; ?>">Обслуживание</a>
        <?php endif; ?>
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
        <form method="POST" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="add_procedure">

            <div style="grid-column: span 2;">
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
                <label>Начало работы</label>
                <input type="time" name="work_start" class="form-control" value="08:00" required>
            </div>
            <div>
                <label>Конец работы</label>
                <input type="time" name="work_end" class="form-control" value="17:00" required>
            </div>
            <div>
                <label>Кабинет</label>
                <input type="text" name="default_cabinet" class="form-control" placeholder="101">
            </div>

            <div>
                <label>Цена (руб)</label>
                <input type="number" step="0.01" name="price" class="form-control" value="0" required>
            </div>
            <div style="display: flex; align-items: center; gap: 5px; padding-bottom: 10px;">
                <input type="checkbox" name="is_paid" id="is_paid" checked>
                <label for="is_paid">Платная</label>
            </div>

            <div style="grid-column: span 3;">
                <label>Закрепленные сотрудники (Медсестры)</label>
                <div style="display: flex; flex-wrap: wrap; gap: 10px; padding: 10px; border: 1px solid var(--win-border); border-radius: 4px; background: rgba(255,255,255,0.3);">
                    <?php foreach ($allStaff as $s): if ($s['role'] !== 'nurse') continue; ?>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="checkbox" name="assigned_staff[]" value="<?php echo $s['id']; ?>">
                            <?php echo htmlspecialchars($s['name']); ?>
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
                    <th style="padding: 10px;">Кабинет</th>
                    <th style="padding: 10px;">Время (Д+П)</th>
                    <th style="padding: 10px;">График</th>
                    <th style="padding: 10px;">Цена</th>
                    <th style="padding: 10px;">Персонал</th>
                    <th style="padding: 10px; text-align: right;">Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allProcedures as $p): ?>
                    <tr style="border-bottom: 1px solid var(--win-border);">
                        <td style="padding: 10px; font-weight: 500;"><?php echo htmlspecialchars($p['name']); ?></td>
                        <td style="padding: 10px;"><?php echo htmlspecialchars($p['default_cabinet'] ?? '-'); ?></td>
                        <td style="padding: 10px;"><?php echo $p['duration']; ?> + <?php echo $p['prep_time'] ?? 0; ?> мин</td>
                        <td style="padding: 10px; font-size: 0.85rem;">
                            <?php echo $p['work_start'] ?? '08:00'; ?> — <?php echo $p['work_end'] ?? '17:00'; ?>
                        </td>
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
                            <button onclick='openEditProcModal(<?php echo json_encode($p); ?>)' style="background: none; border: none; color: var(--win-accent); cursor: pointer; margin-right: 10px;"><i data-lucide="edit" class="icon"></i></button>
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
        <form method="POST" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
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
                    <option value="consultant">Врач-консультант</option>
                    <option value="nurse">Медсестра</option>
                    <option value="registrar">Медрегистратор</option>
                    <option value="cashier">Кассир</option>
                    <option value="chief">Начмед</option>
                    <option value="admin">Админ</option>
                </select>
            </div>
            <div>
                <label>Специализация</label>
                <input type="text" name="specialization" class="form-control" placeholder="Терапевт">
            </div>
            <div>
                <label>Код доступа</label>
                <input type="text" name="access_code" class="form-control" placeholder="6 цифр" maxlength="6" required>
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
                    <th style="padding: 10px;">Код доступа</th>
                    <th style="padding: 10px; text-align: right;">Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allStaff as $s): ?>
                    <tr style="border-bottom: 1px solid var(--win-border);">
                        <td style="padding: 10px; font-weight: 500;"><?php echo htmlspecialchars($s['name']); ?></td>
                        <td style="padding: 10px;"><?php echo $s['role']; ?></td>
                        <td style="padding: 10px;"><?php echo htmlspecialchars($s['specialization']); ?></td>
                        <td style="padding: 10px;"><code style="background: #f0f0f0; padding: 2px 4px; border-radius: 3px;"><?php echo $s['access_code'] ?? '------'; ?></code></td>
                        <td style="padding: 10px; text-align: right;">
                            <button onclick='openEditStaffModal(<?php echo json_encode($s); ?>)' style="background: none; border: none; color: var(--win-accent); cursor: pointer; margin-right: 10px;"><i data-lucide="edit" class="icon"></i></button>
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

<?php elseif ($activeSub === 'details'):
    $details = (new \Medical\Core\JsonStore('settings'))->getAll();
?>
    <div class="card mica-effect">
        <h2>Реквизиты организации</h2>
        <form method="POST" style="max-width: 600px;">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="save_sanatorium_details">

            <div class="mb-3">
                <label class="form-label">Полное наименование организации</label>
                <input type="text" name="org_name" class="form-control" value="<?php echo htmlspecialchars($details['org_name'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Адрес</label>
                <input type="text" name="org_address" class="form-control" value="<?php echo htmlspecialchars($details['org_address'] ?? ''); ?>">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="mb-3">
                    <label class="form-label">Телефон</label>
                    <input type="text" name="org_phone" class="form-control" value="<?php echo htmlspecialchars($details['org_phone'] ?? '+375 '); ?>" placeholder="+375 (__) ___-__-__">
                </div>
                <div class="mb-3">
                    <label class="form-label">УНП / ИНН</label>
                    <input type="text" name="org_unp" class="form-control" value="<?php echo htmlspecialchars($details['org_unp'] ?? ''); ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Банковские реквизиты (Банк, БИК)</label>
                <input type="text" name="org_bank" class="form-control" value="<?php echo htmlspecialchars($details['org_bank'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Расчетный счет (IBAN)</label>
                <input type="text" name="org_account" class="form-control" value="<?php echo htmlspecialchars($details['org_account'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">ФИО руководителя</label>
                <input type="text" name="org_director" class="form-control" value="<?php echo htmlspecialchars($details['org_director'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary">Сохранить реквизиты</button>
        </form>
    </div>

<?php elseif ($activeSub === 'database'):
    $db = (new \Medical\Core\JsonStore('settings'))->getAll();
    $missingTables = [];
    $dbError = null;
    if (($db['db_driver'] ?? 'json') === 'mysql') {
        $res = \Medical\Core\DB::checkTables();
        if (isset($res['error'])) {
            $dbError = $res['error'];
        } else {
            $missingTables = $res;
        }
    }
?>
    <div class="card mica-effect">
        <h2>Настройки базы данных</h2>
        <p style="color: var(--win-text-secondary); margin-bottom: 20px;">
            Вы можете использовать локальные JSON файлы (по умолчанию) или подключить внешнюю базу данных MySQL для повышения производительности.
        </p>

        <?php if ($dbError): ?>
            <div style="background: #fde7e9; color: #d13438; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #d13438;">
                <strong>Ошибка подключения:</strong> <?php echo htmlspecialchars($dbError); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($missingTables)): ?>
            <div style="background: #fff8e1; color: #b7791f; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fbd38d;">
                <strong>Внимание:</strong> В базе данных MySQL отсутствуют необходимые таблицы (<?php echo implode(', ', $missingTables); ?>).
                <form method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                    <input type="hidden" name="action" value="init_mysql">
                    <button type="submit" class="btn btn-primary btn-sm">Создать таблицы</button>
                </form>
            </div>
        <?php endif; ?>

        <form method="POST" style="max-width: 500px;">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="save_db_settings">

            <div class="mb-3">
                <label class="form-label">Тип хранилища (Driver)</label>
                <select name="db_driver" class="form-control">
                    <option value="json" <?php echo ($db['db_driver'] ?? 'json') === 'json' ? 'selected' : ''; ?>>JSON Файлы (Локально)</option>
                    <option value="mysql" <?php echo ($db['db_driver'] ?? 'json') === 'mysql' ? 'selected' : ''; ?>>MySQL Server</option>
                </select>
            </div>

            <div id="mysql_fields" style="display: <?php echo ($db['db_driver'] ?? 'json') === 'mysql' ? 'block' : 'none'; ?>; background: rgba(0,0,0,0.02); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                <div class="mb-3">
                    <label class="form-label">Host</label>
                    <input type="text" name="db_host" class="form-control" value="<?php echo htmlspecialchars($db['db_host'] ?? 'localhost'); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Имя базы данных (Database Name)</label>
                    <input type="text" name="db_name" class="form-control" value="<?php echo htmlspecialchars($db['db_name'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Пользователь</label>
                    <input type="text" name="db_user" class="form-control" value="<?php echo htmlspecialchars($db['db_user'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Пароль</label>
                    <input type="password" name="db_pass" class="form-control" value="<?php echo htmlspecialchars($db['db_pass'] ?? ''); ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Сохранить настройки</button>
        </form>
    </div>

    <script>
        document.querySelector('select[name="db_driver"]').addEventListener('change', function() {
            document.getElementById('mysql_fields').style.display = this.value === 'mysql' ? 'block' : 'none';
        });
    </script>

<?php elseif ($activeSub === 'appearance'):
    $ui = (new \Medical\Core\JsonStore('settings'))->getAll();
?>
    <div class="card mica-effect">
        <h2>Настройки внешнего вида</h2>
        <form method="POST" style="max-width: 500px;">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="save_ui_settings">

            <div class="mb-3">
                <label class="form-label">Шрифт (CSS font-family)</label>
                <input type="text" name="font_family" class="form-control" value="<?php echo htmlspecialchars($ui['font_family'] ?? "'Segoe UI', sans-serif"); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Базовый размер шрифта (px)</label>
                <input type="number" name="font_size" class="form-control" value="<?php echo $ui['font_size'] ?? 16; ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Акцентный цвет (HEX)</label>
                <input type="color" name="accent_color" class="form-control" value="<?php echo $ui['accent_color'] ?? '#0078d4'; ?>" style="height: 40px;">
            </div>
            <div class="mb-3">
                <label class="form-label">Скругление углов (px)</label>
                <input type="number" name="border_radius" class="form-control" value="<?php echo $ui['border_radius'] ?? 8; ?>">
            </div>

            <button type="submit" class="btn btn-primary">Сохранить</button>
        </form>
    </div>
<?php elseif ($activeSub === 'maintenance' && \Medical\Core\Auth::isAdmin()): ?>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <div class="card mica-effect">
            <h2>Резервное копирование</h2>
            <p style="color: var(--win-text-secondary); margin-bottom: 20px;">
                Создайте полную копию всех данных системы (пациенты, назначения, персонал, настройки) в формате ZIP.
            </p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                <input type="hidden" name="action" value="backup_system">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i data-lucide="download" class="icon"></i> Скачать резервную копию
                </button>
            </form>

            <hr style="border:0; border-top: 1px solid var(--win-border); margin: 30px 0;">

            <h2>Восстановление данных</h2>
            <p style="color: var(--win-text-secondary); margin-bottom: 20px;">
                Выберите ранее созданный ZIP-архив для восстановления данных. <strong>Внимание: текущие данные будут перезаписаны!</strong>
            </p>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                <input type="hidden" name="action" value="restore_system">
                <div class="mb-3">
                    <input type="file" name="backup_file" class="form-control" accept=".zip" required>
                </div>
                <button type="submit" class="btn" style="width: 100%;">
                    <i data-lucide="upload" class="icon"></i> Восстановить из файла
                </button>
            </form>
        </div>

        <div class="card mica-effect" style="border-left: 4px solid #d13438;">
            <h2 style="color: #d13438;">Сброс системы</h2>
            <p style="color: var(--win-text-secondary); margin-bottom: 20px;">
                Это действие безвозвратно удалит всех пациентов, их истории болезни, назначенные процедуры и логи. Справочники процедур и персонала также будут очищены.
            </p>
            <form method="POST" onsubmit="return confirm('Вы уверены, что хотите УДАЛИТЬ ВСЕ ДАННЫЕ? Это действие необратимо.')">
                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                <input type="hidden" name="action" value="reset_system">
                <div class="mb-3">
                    <label class="form-label">Пароль подтверждения</label>
                    <input type="password" name="reset_password" class="form-control" placeholder="Введите пароль для удаления" required>
                </div>
                <button type="submit" class="btn btn-danger" style="width: 100%;">
                    <i data-lucide="trash-2" class="icon"></i> Удалить все данные
                </button>
            </form>
        </div>
    </div>

<?php elseif ($activeSub === 'logs'):
    $logManager = new \Medical\Core\Managers\LogManager();
    $logs = $logManager->getAll(200);
?>
    <div class="card mica-effect">
        <h2>Журнал активности (последние 200)</h2>
        <table style="font-size: 0.85rem;">
            <thead>
                <tr>
                    <th>Время</th>
                    <th>Сотрудник</th>
                    <th>Действие</th>
                    <th>Детали</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $l): ?>
                    <tr>
                        <td><?php echo $l['timestamp']; ?></td>
                        <td><strong><?php echo htmlspecialchars($l['user_name']); ?></strong> (<?php echo $l['role']; ?>)</td>
                        <td><?php echo htmlspecialchars($l['action']); ?></td>
                        <td><pre style="font-size: 0.75rem; margin:0;"><?php echo json_encode($l['details'], JSON_UNESCAPED_UNICODE); ?></pre></td>
                        <td style="color: #999;"><?php echo $l['ip']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Edit Procedure Modal -->
<div id="editProcModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
    <div class="card mica-effect" style="width: 600px; margin: 60px auto; padding: 32px;">
        <h2 style="margin-bottom: 24px;">Редактировать процедуру</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="edit_procedure">
            <input type="hidden" name="id" id="edit_proc_id">

            <div class="mb-3">
                <label>Наименование</label>
                <input type="text" name="name" id="edit_proc_name" class="form-control" style="width: 100%;" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="mb-3">
                    <label>Длит. (мин)</label>
                    <input type="number" name="duration" id="edit_proc_dur" class="form-control" style="width: 100%;" required>
                </div>
                <div class="mb-3">
                    <label>Подг. (мин)</label>
                    <input type="number" name="prep_time" id="edit_proc_prep" class="form-control" style="width: 100%;" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="mb-3">
                    <label>Начало работы</label>
                    <input type="time" name="work_start" id="edit_proc_ws" class="form-control" style="width: 100%;" required>
                </div>
                <div class="mb-3">
                    <label>Конец работы</label>
                    <input type="time" name="work_end" id="edit_proc_we" class="form-control" style="width: 100%;" required>
                </div>
            </div>
            <div class="mb-3">
                <label>Кабинет</label>
                <input type="text" name="default_cabinet" id="edit_proc_cab" class="form-control" style="width: 100%;">
            </div>
            <div style="display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: center;">
                <div class="mb-3">
                    <label>Цена (руб)</label>
                    <input type="number" step="0.01" name="price" id="edit_proc_price" class="form-control" style="width: 100%;" required>
                </div>
                <div class="mb-3" style="padding-top: 20px;">
                    <input type="checkbox" name="is_paid" id="edit_proc_is_paid">
                    <label for="edit_proc_is_paid">Платная</label>
                </div>
            </div>

            <div class="mb-3">
                <label>Закрепленные медсестры</label>
                <div id="edit_proc_staff_list" style="display: flex; flex-wrap: wrap; gap: 10px; padding: 10px; border: 1px solid var(--win-border); border-radius: 4px;">
                    <?php foreach ($allStaff as $s): if ($s['role'] !== 'nurse') continue; ?>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="assigned_staff[]" value="<?php echo $s['id']; ?>" class="edit-staff-checkbox">
                            <?php echo htmlspecialchars($s['name']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                <button type="button" class="btn" onclick="document.getElementById('editProcModal').style.display='none'">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Staff Modal -->
<div id="editStaffModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
    <div class="card mica-effect" style="width: 500px; margin: 80px auto; padding: 32px;">
        <h2 style="margin-bottom: 24px;">Редактировать сотрудника</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="edit_staff">
            <input type="hidden" name="id" id="edit_staff_id">

            <div class="mb-3">
                <label>ФИО</label>
                <input type="text" name="name" id="edit_staff_name" class="form-control" style="width: 100%;" required>
            </div>
            <div class="mb-3">
                <label>Роль</label>
                <select name="role" id="edit_staff_role" class="form-control" style="width: 100%;">
                    <option value="doctor">Врач</option>
                    <option value="consultant">Врач-консультант</option>
                    <option value="nurse">Медсестра</option>
                    <option value="registrar">Медрегистратор</option>
                    <option value="cashier">Кассир</option>
                    <option value="chief">Начмед</option>
                    <option value="admin">Админ</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Специализация</label>
                <input type="text" name="specialization" id="edit_staff_spec" class="form-control" style="width: 100%;">
            </div>
            <div class="mb-3">
                <label>Код доступа (6 цифр)</label>
                <input type="text" name="access_code" id="edit_staff_code" class="form-control" style="width: 100%;" maxlength="6" required>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                <button type="button" class="btn" onclick="document.getElementById('editStaffModal').style.display='none'">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditProcModal(p) {
    document.getElementById('edit_proc_id').value = p.id;
    document.getElementById('edit_proc_name').value = p.name;
    document.getElementById('edit_proc_dur').value = p.duration;
    document.getElementById('edit_proc_prep').value = p.prep_time || 0;
    document.getElementById('edit_proc_ws').value = p.work_start || '08:00';
    document.getElementById('edit_proc_we').value = p.work_end || '17:00';
    document.getElementById('edit_proc_cab').value = p.default_cabinet || '';
    document.getElementById('edit_proc_price').value = p.price;
    document.getElementById('edit_proc_is_paid').checked = !!p.is_paid;

    // Set checkboxes
    const assigned = p.assigned_staff || [];
    document.querySelectorAll('.edit-staff-checkbox').forEach(cb => {
        cb.checked = assigned.includes(cb.value);
    });

    document.getElementById('editProcModal').style.display = 'block';
}

function openEditStaffModal(staff) {
    document.getElementById('edit_staff_id').value = staff.id;
    document.getElementById('edit_staff_name').value = staff.name;
    document.getElementById('edit_staff_role').value = staff.role;
    document.getElementById('edit_staff_spec').value = staff.specialization || '';
    document.getElementById('edit_staff_code').value = staff.access_code || '';
    document.getElementById('editStaffModal').style.display = 'block';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
