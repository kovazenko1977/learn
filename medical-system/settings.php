<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

$currentUser = \Medical\Core\Auth::getUser();

if (!\Medical\Core\Auth::can('settings_staff') && !\Medical\Core\Auth::can('settings_procs') && !\Medical\Core\Auth::can('settings_system')) {
    echo '<div class="card mica-effect"><h2>Доступ ограничен</h2><p>У вас нет прав для изменения настроек.</p></div>';
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

    if ($action === 'add_package' && \Medical\Core\Auth::can('settings_procs')) {
        $pm = new \Medical\Core\Managers\PackageManager();
        $items = [];
        if (isset($_POST['proc_ids'])) {
            foreach ($_POST['proc_ids'] as $index => $pid) {
                $items[] = [
                    'procedure_id' => $pid,
                    'quantity' => (int)$_POST['proc_qtys'][$index]
                ];
            }
        }
        $pm->add([
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? '',
            'items' => $items
        ]);
        $message = 'Пакет процедур создан';
    } elseif ($action === 'edit_package' && \Medical\Core\Auth::can('settings_procs')) {
        $pm = new \Medical\Core\Managers\PackageManager();
        $items = [];
        if (isset($_POST['proc_ids'])) {
            foreach ($_POST['proc_ids'] as $index => $pid) {
                $items[] = [
                    'procedure_id' => $pid,
                    'quantity' => (int)$_POST['proc_qtys'][$index]
                ];
            }
        }
        $pm->update($_POST['id'], [
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? '',
            'items' => $items
        ]);
        $message = 'Пакет обновлен';
    } elseif ($action === 'delete_package' && \Medical\Core\Auth::can('settings_procs')) {
        $pm = new \Medical\Core\Managers\PackageManager();
        $pm->delete($_POST['id']);
        $message = 'Пакет удален';
    } elseif ($action === 'add_staff' && \Medical\Core\Auth::canManageStaff()) {
        $staffManager->create([
            'name' => $_POST['name'],
            'role' => $_POST['role'],
            'specialization' => $_POST['specialization'],
            'access_code' => $_POST['access_code'],
            'permissions' => $_POST['perms'] ?? []
        ]);
        $message = 'Сотрудник добавлен';
    } elseif ($action === 'edit_staff' && \Medical\Core\Auth::canManageStaff()) {
        $staffManager->update($_POST['id'], [
            'name' => $_POST['name'],
            'role' => $_POST['role'],
            'specialization' => $_POST['specialization'],
            'access_code' => $_POST['access_code'],
            'permissions' => $_POST['perms'] ?? []
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
    } elseif ($action === 'import_procedures' && \Medical\Core\Auth::can('settings_procs') && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file']['tmp_name'];
        if (($handle = fopen($file, "r")) !== FALSE) {
            $bom = fread($handle, 3);
            if ($bom != "\xEF\xBB\xBF") rewind($handle);
            $headers = fgetcsv($handle, 1000, ",");
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) < 2) continue;
                // Expected order from export: ID, Name, Duration, Prep, Price, IsPaid, Cabinet
                $pData = [
                    'name' => $data[1] ?? '',
                    'duration' => (int)($data[2] ?? 20),
                    'prep_time' => (int)($data[3] ?? 5),
                    'price' => (float)($data[4] ?? 0),
                    'is_paid' => ($data[5] ?? '1') === '1',
                    'default_cabinet' => $data[6] ?? '',
                    'work_start' => '08:00',
                    'work_end' => '17:00'
                ];
                if (!empty($pData['name'])) {
                    $procedureManager->add($pData);
                }
            }
            fclose($handle);
            $message = 'Справочник процедур обновлен из файла';
        }
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
    } elseif ($action === 'delete_overdue_unpaid') {
        $sm = new \Medical\Core\Managers\ScheduleManager();
        $count = $sm->deleteOverdueUnpaid();
        $message = "Удалено $count просроченных неоплаченных процедур.";
    } elseif ($action === 'save_system_settings') {
        $settingsStore = new \Medical\Core\JsonStore('settings');
        $existing = $settingsStore->getAll();
        $settingsStore->save(array_merge($existing, [
            'is_logging_enabled' => isset($_POST['is_logging_enabled']),
            'is_booking_enabled' => isset($_POST['is_booking_enabled'])
        ]));
        $message = 'Системные настройки сохранены';
    } elseif ($action === 'save_db_settings') {
        $settingsStore = new \Medical\Core\JsonStore('settings');
        $existing = $settingsStore->getAll();
        $oldDriver = $existing['db_driver'] ?? 'json';
        $newDriver = $_POST['db_driver'];

        $dbSettings = array_merge($existing, [
            'db_driver' => $newDriver,
            'db_host' => $_POST['db_host'],
            'db_name' => $_POST['db_name'],
            'db_user' => $_POST['db_user'],
            'db_pass' => $_POST['db_pass']
        ]);
        $settingsStore->save($dbSettings);

        $message = 'Настройки базы данных сохранены. ' . ($newDriver === 'mysql' ? 'Переключено на MySQL.' : 'Используется JSON.');

        if (isset($_POST['migrate_data']) && $oldDriver !== $newDriver) {
            try {
                $migrator = new \Medical\Core\Managers\MigrationManager();
                if ($migrator->migrate($oldDriver, $newDriver)) {
                    $message .= ' Все данные успешно перенесены в новый формат.';
                    (new \Medical\Core\Managers\LogManager())->log('Миграция данных', ['from' => $oldDriver, 'to' => $newDriver]);
                }
            } catch (\Exception $e) {
                $message .= ' Ошибка миграции: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'save_template') {
        $tm = new \Medical\Core\Managers\TemplateManager();
        $tm->save($_POST['type'], $_POST['content']);
        $message = 'Шаблон "' . $_POST['type'] . '" сохранен';
    } elseif ($action === 'init_mysql') {
        try {
            if (\Medical\Core\DB::initTables()) {
                $message = 'Таблицы MySQL успешно созданы/проверены.';
            }
        } catch (\Exception $e) {
            $message = 'Ошибка инициализации MySQL: ' . $e->getMessage();
        }
    } elseif ($action === 'create_snapshot' && \Medical\Core\Auth::can('settings_system')) {
        $sm = new \Medical\Core\Managers\SnapshotManager();
        if ($sm->createSnapshot($_POST['note'] ?? '')) {
            $message = 'Снимок системы (версия) успешно создан';
        }
    } elseif ($action === 'restore_snapshot' && \Medical\Core\Auth::can('settings_system')) {
        $sm = new \Medical\Core\Managers\SnapshotManager();
        if ($sm->restoreFromSnapshot($_POST['id'])) {
            $message = 'Система успешно откачена к выбранной версии';
        }
    } elseif ($action === 'delete_snapshot' && \Medical\Core\Auth::can('settings_system')) {
        $sm = new \Medical\Core\Managers\SnapshotManager();
        if ($sm->deleteSnapshot($_POST['id'])) {
            $message = 'Версия данных удалена';
        }
    } elseif ($action === 'clear_logs' && \Medical\Core\Auth::isAdmin()) {
        $lm = new \Medical\Core\Managers\LogManager();
        if ($lm->clear()) {
            $message = 'Журнал активности очищен';
        }
    } elseif ($action === 'add_room' && \Medical\Core\Auth::can('settings_system')) {
        $rm = new \Medical\Core\Managers\RoomManager();
        $photos = [];
        if (isset($_FILES['photos'])) {
            $uploadDir = __DIR__ . '/uploads/rooms/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                    $name = uniqid() . '_' . $_FILES['photos']['name'][$key];
                    if (move_uploaded_file($tmpName, $uploadDir . $name)) {
                        $photos[] = 'uploads/rooms/' . $name;
                    }
                }
            }
        }
        $rm->add([
            'name' => $_POST['name'],
            'type' => $_POST['type'],
            'capacity' => (int)$_POST['capacity'],
            'base_price' => (float)$_POST['base_price'],
            'description' => $_POST['description'],
            'photos' => $photos
        ]);
        $message = 'Номер добавлен в фонд';
    } elseif ($action === 'edit_room' && \Medical\Core\Auth::can('settings_system')) {
        $rm = new \Medical\Core\Managers\RoomManager();
        $rm->update($_POST['id'], [
            'name' => $_POST['name'],
            'type' => $_POST['type'],
            'capacity' => (int)$_POST['capacity'],
            'base_price' => (float)$_POST['base_price'],
            'description' => $_POST['description']
        ]);
        $message = 'Данные номера обновлены';
    } elseif ($action === 'delete_room' && \Medical\Core\Auth::can('settings_system')) {
        $rm = new \Medical\Core\Managers\RoomManager();
        $rm->delete($_POST['id']);
        $message = 'Номер удален';
    } elseif ($action === 'add_pricing_rule' && \Medical\Core\Auth::can('settings_system')) {
        $rulesStore = new \Medical\Core\JsonStore('pricing_rules');
        $rulesStore->add([
            'id' => uniqid(),
            'name' => $_POST['name'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'type' => $_POST['type'],
            'value' => (float)$_POST['value']
        ]);
        $message = 'Правило ценообразования добавлено';
    } elseif ($action === 'delete_pricing_rule' && \Medical\Core\Auth::can('settings_system')) {
        $rulesStore = new \Medical\Core\JsonStore('pricing_rules');
        $rulesStore->deleteById($_POST['id']);
        $message = 'Правило удалено';
    } elseif ($action === 'add_announcement' && \Medical\Core\Auth::isAdmin()) {
        $am = new \Medical\Core\Managers\AnnouncementManager();
        $am->add([
            'title' => $_POST['title'],
            'content' => $_POST['content'],
            'expires_at' => $_POST['expires_at'] ?? ''
        ]);
        $message = 'Объявление опубликовано';
    } elseif ($action === 'delete_announcement' && \Medical\Core\Auth::isAdmin()) {
        $am = new \Medical\Core\Managers\AnnouncementManager();
        $am->delete($_POST['id']);
        $message = 'Объявление удалено';
    }
}

require_once __DIR__ . '/includes/header.php';

$activeSub = $_GET['sub'] ?? 'procedures';
$allStaff = $staffManager->getAll();
$allProcedures = $procedureManager->getAll();

$permissions = [
    'patients_view' => 'Просмотр реестра пациентов',
    'patients_edit' => 'Добавление/Редактирование пациентов',
    'patients_delete' => 'Удаление пациентов',
    'history_view' => 'Просмотр истории болезни',
    'history_add' => 'Записи в историю болезни',
    'lab_view' => 'Просмотр результатов анализов',
    'lab_upload' => 'Загрузка результатов анализов',
    'procedures_assign' => 'Назначение процедур',
    'procedures_cancel' => 'Отмена назначенных процедур',
    'procedures_delete' => 'Полное удаление назначений (ошибок)',
    'procedures_nurse' => 'Отметка о выполнении (Медсестра)',
    'finance_view' => 'Просмотр финансовых данных (Выручка)',
    'finance_pay' => 'Прием оплаты (Кассир)',
    'analytics_view' => 'Доступ к аналитике',
    'logs_view' => 'Просмотр журнала событий',
    'settings_staff' => 'Управление персоналом',
    'settings_procs' => 'Управление справочником процедур',
    'settings_system' => 'Системные настройки и БД'
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Настройки и Справочники</h1>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <?php if (\Medical\Core\Auth::can('settings_procs')): ?>
            <a href="?sub=procedures" class="btn <?php echo $activeSub === 'procedures' ? 'btn-primary' : ''; ?>">Процедуры</a>
            <a href="?sub=packages" class="btn <?php echo $activeSub === 'packages' ? 'btn-primary' : ''; ?>">Пакеты</a>
        <?php endif; ?>
        <?php if (\Medical\Core\Auth::can('settings_staff')): ?>
            <a href="?sub=staff" class="btn <?php echo $activeSub === 'staff' ? 'btn-primary' : ''; ?>">Персонал</a>
        <?php endif; ?>
        <?php if (\Medical\Core\Auth::can('settings_system')): ?>
            <a href="?sub=details" class="btn <?php echo $activeSub === 'details' ? 'btn-primary' : ''; ?>">Реквизиты</a>
            <a href="?sub=database" class="btn <?php echo $activeSub === 'database' ? 'btn-primary' : ''; ?>">База данных</a>
            <a href="?sub=templates" class="btn <?php echo $activeSub === 'templates' ? 'btn-primary' : ''; ?>">Шаблоны печати</a>
        <?php endif; ?>
        <a href="?sub=appearance" class="btn <?php echo $activeSub === 'appearance' ? 'btn-primary' : ''; ?>">Внешний вид</a>
        <?php if (\Medical\Core\Auth::can('logs_view')): ?>
            <a href="?sub=logs" class="btn <?php echo $activeSub === 'logs' ? 'btn-primary' : ''; ?>">Логи</a>
        <?php endif; ?>
        <?php if (\Medical\Core\Auth::can('settings_system')): ?>
            <a href="?sub=announcements" class="btn <?php echo $activeSub === 'announcements' ? 'btn-primary' : ''; ?>">Объявления</a>
            <a href="?sub=maintenance" class="btn <?php echo $activeSub === 'maintenance' ? 'btn-primary' : ''; ?>">Обслуживание</a>
            <a href="?sub=booking_config" class="btn <?php echo $activeSub === 'booking_config' ? 'btn-primary' : ''; ?>">Настройка Брони</a>
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

<?php elseif ($activeSub === 'packages' && \Medical\Core\Auth::can('settings_procs')):
    $pm = new \Medical\Core\Managers\PackageManager();
    $packages = $pm->getAll();
?>
    <div class="card mica-effect mb-4">
        <h2>Создать пакет процедур</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="add_package">

            <div class="mb-3">
                <label>Название пакета</label>
                <input type="text" name="name" class="form-control" placeholder="Напр. Здоровое сердце" required>
            </div>

            <div class="mb-3">
                <label>Состав пакета</label>
                <div id="package_items_container">
                    <div class="package-item row mb-2" style="display: grid; grid-template-columns: 3fr 1fr auto; gap: 10px; align-items: center;">
                        <select name="proc_ids[]" class="form-control" required>
                            <option value="">-- Выберите процедуру --</option>
                            <?php foreach ($allProcedures as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="proc_qtys[]" class="form-control" value="5" min="1" required>
                        <button type="button" class="btn btn-sm" onclick="this.parentElement.remove()" style="color: #d13438;">&times;</button>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-ghost" onclick="addPackageItem()" style="margin-top: 10px;">+ Добавить строку</button>
            </div>

            <button type="submit" class="btn btn-primary">Создать пакет</button>
        </form>
    </div>

    <script>
        function addPackageItem() {
            const container = document.getElementById('package_items_container');
            const div = document.createElement('div');
            div.className = 'package-item row mb-2';
            div.style.cssText = 'display: grid; grid-template-columns: 3fr 1fr auto; gap: 10px; align-items: center;';
            div.innerHTML = `
                <select name="proc_ids[]" class="form-control" required>
                    <option value="">-- Выберите процедуру --</option>
                    <?php foreach ($allProcedures as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="proc_qtys[]" class="form-control" value="5" min="1" required>
                <button type="button" class="btn btn-sm" onclick="this.parentElement.remove()" style="color: #d13438;">&times;</button>
            `;
            container.appendChild(div);
        }
    </script>

    <div class="card mica-effect">
        <h2>Список пакетов</h2>
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Состав (количество)</th>
                    <th style="text-align: right;">Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($packages as $pkg): ?>
                    <tr style="border-top: 1px solid var(--win-border);">
                        <td style="padding: 15px;"><strong><?php echo htmlspecialchars($pkg['name']); ?></strong></td>
                        <td style="padding: 15px;">
                            <?php
                            foreach ($pkg['items'] as $item) {
                                $proc = $procedureManager->getById($item['procedure_id']);
                                echo '<div style="font-size: 0.85rem;">' . ($proc ? htmlspecialchars($proc['name']) : '???') . ' &mdash; <strong>' . $item['quantity'] . ' шт.</strong></div>';
                            }
                            ?>
                        </td>
                        <td style="padding: 15px; text-align: right;">
                            <button onclick='openEditPackageModal(<?php echo htmlspecialchars(json_encode($pkg), ENT_QUOTES); ?>)' style="background: none; border: none; color: var(--win-accent); cursor: pointer; margin-right: 10px;"><i data-lucide="edit" class="icon"></i></button>
                            <form method="POST" onsubmit="return confirm('Удалить пакет?')">
                                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                <input type="hidden" name="action" value="delete_package">
                                <input type="hidden" name="id" value="<?php echo $pkg['id']; ?>">
                                <button type="submit" style="background:none; border:none; color: #d13438;"><i data-lucide="trash-2" class="icon-sm"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($packages)): ?>
                    <tr><td colspan="3" style="text-align:center; padding: 20px; color: #999;">Пакеты еще не созданы</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($activeSub === 'procedures'): ?>
    <div class="card mica-effect mb-4">
        <h2>Добавить процедуру</h2>
            <div style="display: flex; gap: 10px;">
                <a href="export.php?action=export_procedures" class="btn btn-sm">
                    <i data-lucide="download" class="icon"></i> Экспорт
                </a>
                <button class="btn btn-sm" onclick="document.getElementById('importProcsModal').style.display='block'">
                    <i data-lucide="upload" class="icon"></i> Импорт
                </button>
            </div>
        </div>
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
                            <button onclick='openEditProcModal(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES); ?>)' style="background: none; border: none; color: var(--win-accent); cursor: pointer; margin-right: 10px;"><i data-lucide="edit" class="icon"></i></button>
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
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="add_staff">

            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label>ФИО</label>
                    <input type="text" name="name" class="form-control" style="width:100%" required>
                </div>
                <div>
                    <label>Роль (Шаблон прав)</label>
                    <select name="role" class="form-control" style="width:100%" onchange="applyRoleTemplate(this.value, 'add')">
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
                    <input type="text" name="specialization" class="form-control" style="width:100%" placeholder="Терапевт">
                </div>
                <div>
                    <label>Код доступа</label>
                    <input type="text" name="access_code" class="form-control" style="width:100%" placeholder="6 цифр" maxlength="6" required>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="font-weight: 600; display: block; margin-bottom: 10px;">Индивидуальные права доступа:</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; padding: 15px; background: rgba(0,0,0,0.02); border-radius: 8px;">
                    <?php foreach ($permissions as $key => $label): ?>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; cursor: pointer;">
                            <input type="checkbox" name="perms[]" value="<?php echo $key; ?>" class="perm-add-<?php echo $key; ?>"> <?php echo $label; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Создать сотрудника</button>
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
                            <button onclick='openEditStaffModal(<?php echo htmlspecialchars(json_encode($s), ENT_QUOTES); ?>)' style="background: none; border: none; color: var(--win-accent); cursor: pointer; margin-right: 10px;"><i data-lucide="edit" class="icon"></i></button>
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

            <div class="mb-4" style="background: #fff8e1; padding: 15px; border-radius: 8px; border: 1px solid #fbd38d;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #856404; font-weight: 500;">
                    <input type="checkbox" name="migrate_data" value="1">
                    Перенести все данные из текущей БД в новую (Миграция)
                </label>
                <p style="margin: 5px 0 0 25px; font-size: 0.85rem; color: #856404;">
                    Внимание: Данные в целевой базе будут перезаписаны! Рекомендуется сделать резервную копию перед миграцией.
                </p>
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
    <div class="card mica-effect mb-4">
        <h2>Общие настройки системы</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="save_system_settings">

            <?php $sys = (new \Medical\Core\JsonStore('settings'))->getAll(); ?>
            <div class="mb-3">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="is_logging_enabled" <?php echo (!isset($sys['is_logging_enabled']) || $sys['is_logging_enabled']) ? 'checked' : ''; ?>>
                    Включить журнал активности (логирование действий пользователей)
                </label>
            </div>
            <div class="mb-3">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="is_booking_enabled" <?php echo ($sys['is_booking_enabled'] ?? false) ? 'checked' : ''; ?>>
                    Включить модуль «БРОНИРОВАНИЕ НОМЕРОВ»
                </label>
            </div>

            <button type="submit" class="btn btn-primary">Сохранить настройки</button>
        </form>
    </div>

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

        <div class="card mica-effect" style="margin-bottom: 24px;">
            <h2>Контроль версий и откат</h2>
            <p style="color: var(--win-text-secondary); margin-bottom: 20px;">
                Создавайте «снимки» текущего состояния базы данных перед важными изменениями. Это позволит быстро вернуться к предыдущей версии в случае ошибки.
            </p>
            <form method="POST" style="display: flex; gap: 10px; margin-bottom: 24px;">
                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                <input type="hidden" name="action" value="create_snapshot">
                <input type="text" name="note" placeholder="Примечание к версии..." style="flex-grow: 1;">
                <button type="submit" class="btn btn-primary">Создать снимок</button>
            </form>

            <table style="font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th>Время</th>
                        <th>Автор</th>
                        <th>Примечание</th>
                        <th style="text-align: right;">Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sm = new \Medical\Core\Managers\SnapshotManager();
                    $snapshots = $sm->listSnapshots();
                    foreach ($snapshots as $s): ?>
                        <tr>
                            <td><strong><?php echo $s['timestamp']; ?></strong></td>
                            <td><?php echo htmlspecialchars($s['user']); ?></td>
                            <td><?php echo htmlspecialchars($s['note']); ?></td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <form method="POST" onsubmit="return confirm('Откатить систему к этой версии? Текущие данные будут перезаписаны!')" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="restore_snapshot">
                                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                        <button type="submit" class="btn btn-sm" style="color: var(--win-accent);" title="Откатиться">Откат</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="delete_snapshot">
                                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                        <button type="submit" class="btn btn-sm" style="color: #d13438;" title="Удалить">&times;</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($snapshots)): ?>
                        <tr><td colspan="4" style="text-align: center; color: #999; padding: 20px;">Снимки еще не создавались</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

        <div class="card mica-effect">
            <h2>Очистка данных</h2>
            <p style="color: var(--win-text-secondary); margin-bottom: 20px;">
                Удаление просроченных неоплаченных назначений (старше 2 часов), которые не были отмечены как посещенные.
            </p>
            <form method="POST" onsubmit="return confirm('Удалить просроченные неоплаченные процедуры?')">
                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                <input type="hidden" name="action" value="delete_overdue_unpaid">
                <button type="submit" class="btn" style="width: 100%; border-color: #d13438; color: #d13438;">
                    <i data-lucide="trash" class="icon"></i> Удалить просроченные неоплаченные
                </button>
            </form>
        </div>
    </div>

<?php elseif ($activeSub === 'templates' && \Medical\Core\Auth::can('settings_system')):
    $tm = new \Medical\Core\Managers\TemplateManager();
    $types = [
        'schedule' => 'График процедур',
        'contract' => 'Договор на услуги',
        'epicrisis' => 'Выписной эпикриз'
    ];
    $activeType = $_GET['type'] ?? 'schedule';
    $templateContent = $tm->get($activeType);
?>
    <div class="card mica-effect">
        <h2>Настройка шаблонов печати</h2>
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <?php foreach ($types as $type => $label): ?>
                <a href="?sub=templates&type=<?php echo $type; ?>" class="btn <?php echo $activeType === $type ? 'btn-primary' : ''; ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="save_template">
            <input type="hidden" name="type" value="<?php echo $activeType; ?>">

            <div class="mb-3">
                <label class="form-label">HTML Содержимое шаблона</label>
                <div style="font-size: 0.8rem; color: var(--win-text-secondary); margin-bottom: 10px;">
                    Доступные теги: <code>{{patient_name}}</code>, <code>{{patient_id}}</code>, <code>{{org_name}}</code>, <code>{{org_address}}</code>, <code>{{date}}</code>, <code>{{content}}</code> (основные данные), <code>{{doctor_name}}</code>
                </div>
                <textarea name="content" class="form-control" style="height: 400px; font-family: monospace; font-size: 14px;"><?php echo htmlspecialchars($templateContent); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Сохранить шаблон</button>
        </form>
    </div>

<?php elseif ($activeSub === 'booking_config' && \Medical\Core\Auth::can('settings_system')):
    $rm = new \Medical\Core\Managers\RoomManager();
    $rooms = $rm->getAll();
    $rulesStore = new \Medical\Core\JsonStore('pricing_rules');
    $rules = $rulesStore->getAll();
?>
    <div class="d-grid" style="grid-template-columns: 1.5fr 1fr; gap: 24px;">
        <div>
            <div class="card mica-effect mb-4">
                <h2>Номерной фонд</h2>
                <form method="POST" enctype="multipart/form-data" style="margin-bottom: 20px;">
                    <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                    <input type="hidden" name="action" value="add_room">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px; align-items: end; margin-bottom:10px;">
                        <div>
                            <label>Название/№</label>
                            <input type="text" name="name" class="form-control" placeholder="Люкс 201" required>
                        </div>
                        <div>
                            <label>Тип</label>
                            <select name="type" class="form-control">
                                <option value="single">Одноместный</option>
                                <option value="double">Двухместный</option>
                                <option value="suite">Люкс</option>
                                <option value="apartment">Апартаменты</option>
                            </select>
                        </div>
                        <div>
                            <label>Мест</label>
                            <input type="number" name="capacity" class="form-control" value="2" required>
                        </div>
                        <div>
                            <label>Цена/сут</label>
                            <input type="number" name="base_price" class="form-control" value="5000" required>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 10px; align-items: end;">
                        <div>
                            <label>Описание</label>
                            <input type="text" name="description" class="form-control" placeholder="Уютный номер с видом на парк">
                        </div>
                        <div>
                            <label>Фотографии</label>
                            <input type="file" name="photos[]" class="form-control" multiple accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary" style="height:38px">Добавить номер</button>
                    </div>
                </form>

                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Номер</th>
                            <th>Тип</th>
                            <th>Мест</th>
                            <th>Базовая цена</th>
                            <th style="text-align: right;">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr style="border-top: 1px solid var(--win-border);">
                                <td style="padding: 10px;"><?php echo htmlspecialchars($room['name']); ?></td>
                                <td style="padding: 10px;"><?php echo $room['type']; ?></td>
                                <td style="padding: 10px;"><?php echo $room['capacity']; ?></td>
                                <td style="padding: 10px;"><?php echo number_format($room['base_price'], 2); ?> ₽</td>
                                <td style="padding: 10px; text-align: right;">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить номер?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="delete_room">
                                        <input type="hidden" name="id" value="<?php echo $room['id']; ?>">
                                        <button type="submit" style="background:none; border:none; color: #d13438;"><i data-lucide="trash-2" class="icon-sm"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card mica-effect">
                <h2>Сайт-виджет (Интеграция)</h2>
                <p style="font-size: 0.9rem; color: var(--win-text-secondary); margin-bottom: 10px;">
                    Скопируйте этот код и вставьте его на свой сайт для отображения формы бронирования:
                </p>
                <textarea class="form-control" style="height: 120px; font-family: monospace; font-size: 12px;" readonly><script src="<?php echo (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]"; ?>/medical-system/assets/js/booking-widget.js"></script>
<div id="wes-booking-widget" data-url="<?php echo (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]"; ?>/medical-system/"></div></textarea>
            </div>
        </div>

        <div>
            <div class="card mica-effect">
                <h2>Сезонные цены</h2>
                <form method="POST" style="margin-bottom: 20px;">
                    <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                    <input type="hidden" name="action" value="add_pricing_rule">
                    <div class="mb-2">
                        <label>Название периода</label>
                        <input type="text" name="name" class="form-control" placeholder="Лето 2024" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;" class="mb-2">
                        <div>
                            <label>С</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div>
                            <label>По</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 10px;" class="mb-3">
                        <div>
                            <label>Тип изменения</label>
                            <select name="type" class="form-control">
                                <option value="fixed">Фикс. цена (руб)</option>
                                <option value="multiplier">Коэффициент (1.5 = +50%)</option>
                            </select>
                        </div>
                        <div>
                            <label>Значение</label>
                            <input type="number" step="0.01" name="value" class="form-control" value="1.2" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Добавить правило</button>
                </form>

                <div style="font-size: 0.85rem;">
                    <?php foreach ($rules as $rule): ?>
                        <div style="padding: 10px; border: 1px solid var(--win-border); border-radius: 6px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?php echo htmlspecialchars($rule['name']); ?></strong><br>
                                <span style="color: #666;"><?php echo $rule['start_date']; ?> — <?php echo $rule['end_date']; ?></span><br>
                                <span style="color: var(--win-accent);"><?php echo $rule['type'] === 'fixed' ? $rule['value'].' ₽' : 'x'.$rule['value']; ?></span>
                            </div>
                            <form method="POST" onsubmit="return confirm('Удалить правило?')">
                                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                <input type="hidden" name="action" value="delete_pricing_rule">
                                <input type="hidden" name="id" value="<?php echo $rule['id']; ?>">
                                <button type="submit" style="background:none; border:none; color: #d13438;"><i data-lucide="trash-2" class="icon-sm"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($activeSub === 'announcements' && \Medical\Core\Auth::isAdmin()):
    $am = new \Medical\Core\Managers\AnnouncementManager();
    $announcements = $am->getAll();
?>
    <div class="card mica-effect mb-4">
        <h2>Новое объявление</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="add_announcement">
            <div class="mb-3">
                <label>Заголовок</label>
                <input type="text" name="title" class="form-control" placeholder="Важное сообщение для всех сотрудников" required>
            </div>
            <div class="mb-3">
                <label>Текст объявления</label>
                <textarea name="content" class="form-control" style="height: 100px;" required></textarea>
            </div>
            <div class="mb-3">
                <label>Актуально до (необязательно)</label>
                <input type="date" name="expires_at" class="form-control" style="width: 200px;">
            </div>
            <button type="submit" class="btn btn-primary">Опубликовать</button>
        </form>
    </div>

    <div class="card mica-effect">
        <h2>Список объявлений</h2>
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>Заголовок</th>
                    <th>Автор</th>
                    <th>Дата создания</th>
                    <th>Актуально до</th>
                    <th style="text-align: right;">Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($announcements as $a): ?>
                    <tr style="border-top: 1px solid var(--win-border);">
                        <td style="padding: 15px;">
                            <strong><?php echo htmlspecialchars($a['title']); ?></strong>
                            <div style="font-size: 0.8rem; color: #666; margin-top: 5px;"><?php echo nl2br(htmlspecialchars($a['content'])); ?></div>
                        </td>
                        <td style="padding: 15px;"><?php echo htmlspecialchars($a['author']); ?></td>
                        <td style="padding: 15px; font-size: 0.85rem;"><?php echo $a['created_at']; ?></td>
                        <td style="padding: 15px; font-size: 0.85rem;"><?php echo $a['expires_at'] ?: '-'; ?></td>
                        <td style="padding: 15px; text-align: right;">
                            <form method="POST" onsubmit="return confirm('Удалить объявление?')">
                                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                <input type="hidden" name="action" value="delete_announcement">
                                <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                <button type="submit" style="background:none; border:none; color: #d13438;"><i data-lucide="trash-2" class="icon-sm"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($announcements)): ?>
                    <tr><td colspan="5" style="text-align:center; padding: 20px; color: #999;">Объявлений пока нет</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($activeSub === 'logs'):
    $logManager = new \Medical\Core\Managers\LogManager();
    $logs = $logManager->getAll(200);
?>
    <div class="card mica-effect">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Журнал активности (последние 200)</h2>
            <?php if (\Medical\Core\Auth::isAdmin()): ?>
                <form method="POST" onsubmit="return confirm('Очистить весь журнал?')">
                    <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                    <input type="hidden" name="action" value="clear_logs">
                    <button type="submit" class="btn btn-sm" style="color: #d13438;">
                        <i data-lucide="trash-2" class="icon"></i> Очистить журнал
                    </button>
                </form>
            <?php endif; ?>
        </div>
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

<!-- Import Procedures Modal -->
<div id="importProcsModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
    <div class="card mica-effect" style="width: 440px; margin: 80px auto; padding: 32px;">
        <h2 style="margin-bottom: 24px;">Импорт справочника процедур</h2>
        <p style="font-size: 0.9rem; color: var(--win-text-secondary); margin-bottom: 20px;">
            Выберите CSV файл для импорта. Формат должен соответствовать файлу экспорта (ID, Название, Длительность, Подготовка, Цена, Платная, Кабинет).
        </p>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="import_procedures">
            <div style="margin-bottom: 32px;">
                <input type="file" name="csv_file" accept=".csv" required style="width: 100%;">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn" onclick="document.getElementById('importProcsModal').style.display='none'">Отмена</button>
                <button type="submit" class="btn btn-primary">Загрузить</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Staff Modal -->
<div id="editStaffModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); overflow-y: auto;">
    <div class="card mica-effect" style="width: 800px; margin: 40px auto; padding: 32px;">
        <h2 style="margin-bottom: 24px;">Редактировать сотрудника</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="edit_staff">
            <input type="hidden" name="id" id="edit_staff_id">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label>ФИО</label>
                    <input type="text" name="name" id="edit_staff_name" class="form-control" style="width: 100%;" required>
                </div>
                <div>
                    <label>Роль</label>
                    <select name="role" id="edit_staff_role" class="form-control" style="width: 100%;" onchange="applyRoleTemplate(this.value, 'edit')">
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
                    <input type="text" name="specialization" id="edit_staff_spec" class="form-control" style="width: 100%;">
                </div>
                <div>
                    <label>Код доступа (6 цифр)</label>
                    <input type="text" name="access_code" id="edit_staff_code" class="form-control" style="width: 100%;" maxlength="6" required>
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="font-weight: 600; display: block; margin-bottom: 10px;">Индивидуальные права доступа:</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 15px; background: rgba(0,0,0,0.02); border-radius: 8px;">
                    <?php foreach ($permissions as $key => $label): ?>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; cursor: pointer;">
                            <input type="checkbox" name="perms[]" value="<?php echo $key; ?>" class="perm-edit-<?php echo $key; ?>"> <?php echo $label; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
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

const roleTemplates = {
    'admin': ['patients_view', 'patients_edit', 'patients_delete', 'history_view', 'history_add', 'lab_view', 'lab_upload', 'procedures_assign', 'procedures_cancel', 'procedures_delete', 'procedures_nurse', 'finance_view', 'finance_pay', 'analytics_view', 'logs_view', 'settings_staff', 'settings_procs', 'settings_system'],
    'chief': ['patients_view', 'patients_edit', 'history_view', 'history_add', 'lab_view', 'lab_upload', 'procedures_assign', 'procedures_cancel', 'procedures_delete', 'finance_view', 'analytics_view', 'logs_view', 'settings_staff', 'settings_procs'],
    'doctor': ['patients_view', 'patients_edit', 'history_view', 'history_add', 'lab_view', 'lab_upload', 'procedures_assign', 'procedures_cancel', 'procedures_delete'],
    'consultant': ['patients_view', 'history_view', 'lab_view'],
    'nurse': ['patients_view', 'procedures_nurse'],
    'registrar': ['patients_view', 'patients_edit', 'procedures_assign', 'procedures_cancel', 'procedures_delete'],
    'cashier': ['patients_view', 'finance_pay']
};

function applyRoleTemplate(role, mode) {
    const perms = roleTemplates[role] || [];
    document.querySelectorAll(`input[class*="perm-${mode}-"]`).forEach(cb => {
        cb.checked = perms.includes(cb.value);
    });
}

function openEditStaffModal(staff) {
    document.getElementById('edit_staff_id').value = staff.id;
    document.getElementById('edit_staff_name').value = staff.name;
    document.getElementById('edit_staff_role').value = staff.role;
    document.getElementById('edit_staff_spec').value = staff.specialization || '';
    document.getElementById('edit_staff_code').value = staff.access_code || '';

    // Set checkboxes
    const userPerms = staff.permissions || [];
    document.querySelectorAll('input[class*="perm-edit-"]').forEach(cb => {
        cb.checked = userPerms.includes(cb.value);
    });

    document.getElementById('editStaffModal').style.display = 'block';
}

// Initial templates
window.onload = () => {
    applyRoleTemplate('doctor', 'add');
};
</script>

<!-- Edit Package Modal -->
<div id="editPkgModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
    <div class="card mica-effect" style="width: 600px; margin: 60px auto; padding: 32px;">
        <h2 style="margin-bottom: 24px;">Редактировать пакет</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="edit_package">
            <input type="hidden" name="id" id="edit_pkg_id">

            <div class="mb-3">
                <label>Название пакета</label>
                <input type="text" name="name" id="edit_pkg_name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Состав пакета</label>
                <div id="edit_package_items_container"></div>
                <button type="button" class="btn btn-sm btn-ghost" onclick="addEditPackageItem()" style="margin-top: 10px;">+ Добавить строку</button>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                <button type="button" class="btn" onclick="document.getElementById('editPkgModal').style.display='none'">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditPackageModal(pkg) {
    document.getElementById('edit_pkg_id').value = pkg.id;
    document.getElementById('edit_pkg_name').value = pkg.name;
    const container = document.getElementById('edit_package_items_container');
    container.innerHTML = '';

    pkg.items.forEach(item => {
        addEditPackageItem(item.procedure_id, item.quantity);
    });

    document.getElementById('editPkgModal').style.display = 'block';
}

function addEditPackageItem(procId = '', qty = 5) {
    const container = document.getElementById('edit_package_items_container');
    const div = document.createElement('div');
    div.className = 'package-item row mb-2';
    div.style.cssText = 'display: grid; grid-template-columns: 3fr 1fr auto; gap: 10px; align-items: center;';

    let options = '<option value="">-- Выберите процедуру --</option>';
    <?php foreach ($allProcedures as $p): ?>
        options += `<option value="<?php echo $p['id']; ?>" ${procId == '<?php echo $p['id']; ?>' ? 'selected' : ''}><?php echo htmlspecialchars($p['name']); ?></option>`;
    <?php endforeach; ?>

    div.innerHTML = `
        <select name="proc_ids[]" class="form-control" required>${options}</select>
        <input type="number" name="proc_qtys[]" class="form-control" value="${qty}" min="1" required>
        <button type="button" class="btn btn-sm" onclick="this.parentElement.remove()" style="color: #d13438;">&times;</button>
    `;
    container.appendChild(div);
}
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
