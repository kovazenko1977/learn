<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\BackupManager;
use Hop\Core\DemoDataLoader;

checkRole('admin');

$settingsStore = new JsonStore('data/settings.json');
$settings = $settingsStore->read();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hospital_name'])) {
    checkCsrf();
    $newSettings = [
        'hospital_name' => $_POST['hospital_name'],
        'sla' => [
            'low' => (int)$_POST['sla_low'],
            'medium' => (int)$_POST['sla_medium'],
            'high' => (int)$_POST['sla_high'],
            'critical' => (int)$_POST['sla_critical']
        ],
        'telegram_token' => $_POST['telegram_token'],
        'telegram_chat_id' => $_POST['telegram_chat_id']
    ];

    if ($settingsStore->save($newSettings)) {
        $settings = $newSettings;
        $message = 'Настройки системы успешно обновлены';
    } else {
        $message = 'Ошибка при сохранении настроек';
    }
}

$backupManager = new BackupManager('data', 'uploads');

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'backup') {
        try {
            $file = $backupManager->createBackup();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            readfile($file);
            unlink($file);
            exit;
        } catch (\Exception $e) {
            $message = "Ошибка бэкапа: " . $e->getMessage();
        }
    } elseif ($_GET['action'] === 'demo') {
        $requestStore = new JsonStore('data/requests.json');
        $serviceStore = new JsonStore('data/services.json');
        $userStore = new JsonStore('data/users.json');
        $tmplStore = new JsonStore('data/templates.json');
        $loader = new DemoDataLoader();
        $loader->load($serviceStore, $userStore, $requestStore, $tmplStore);
        header('Location: settings.php?msg=demo_ok');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maintenance'])) {
    checkCsrf();
    if ($_POST['maintenance'] === 'restore' && isset($_FILES['backup_file'])) {
        if ($backupManager->restoreBackup($_FILES['backup_file']['tmp_name'])) {
            $message = 'Данные успешно восстановлены из архива';
        } else {
            $message = 'Ошибка при восстановлении данных';
        }
    } elseif ($_POST['maintenance'] === 'reset') {
        if ($backupManager->resetData($_POST['reset_password'])) {
            $message = 'Система полностью очищена';
        } else {
            $message = 'Неверный административный пароль';
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'demo_ok') {
    $message = 'Демонстрационные данные успешно загружены (120 записей)';
}

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="animation: slideDown 0.5s ease-out;">
        <h1>Настройки ХОП</h1>
        <p style="color:var(--win-text-secondary);">Конфигурация параметров и обслуживание системы</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="animation: slideDown 0.3s ease-out;"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" style="animation: slideUp 0.6s ease-out;">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <div class="form-grid" style="align-items: stretch;">
            <section class="card mica">
                <h2 style="margin-top:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                    <i class="lucide-building-2" style="color:var(--win-accent);"></i> Организация
                </h2>
                <div class="form-group">
                    <label>Название учреждения</label>
                    <input type="text" name="hospital_name" value="<?php echo htmlspecialchars($settings['hospital_name']); ?>" required>
                </div>
            </section>

            <section class="card mica">
                <h2 style="margin-top:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                    <i class="lucide-bell-ring" style="color:var(--win-accent);"></i> Уведомления Telegram
                </h2>
                <div class="form-group">
                    <label>Bot Token</label>
                    <input type="text" name="telegram_token" value="<?php echo htmlspecialchars($settings['telegram_token']); ?>" placeholder="000000000:AAHHH...">
                </div>
                <div class="form-group">
                    <label>Target Chat ID</label>
                    <input type="text" name="telegram_chat_id" value="<?php echo htmlspecialchars($settings['telegram_chat_id']); ?>" placeholder="-100123456789">
                </div>
            </section>
        </div>

        <section class="card mica" style="margin-top: 24px;">
            <h2 style="margin-top:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                <i class="lucide-timer" style="color:var(--win-accent);"></i> Нормативы SLA (в часах)
            </h2>
            <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
                <div class="form-group">
                    <label style="color:var(--priority-low); font-weight:600;">Низкий</label>
                    <input type="number" name="sla_low" value="<?php echo $settings['sla']['low']; ?>" required>
                </div>
                <div class="form-group">
                    <label style="color:var(--priority-medium); font-weight:600;">Средний</label>
                    <input type="number" name="sla_medium" value="<?php echo $settings['sla']['medium']; ?>" required>
                </div>
                <div class="form-group">
                    <label style="color:var(--priority-high); font-weight:600;">Высокий</label>
                    <input type="number" name="sla_high" value="<?php echo $settings['sla']['high']; ?>" required>
                </div>
                <div class="form-group">
                    <label style="color:var(--priority-critical); font-weight:600;">Критический</label>
                    <input type="number" name="sla_critical" value="<?php echo $settings['sla']['critical']; ?>" required>
                </div>
            </div>
        </section>

        <div style="margin-top: 24px; text-align: right;">
            <button type="submit" class="btn-primary" style="padding: 12px 32px; font-size: 16px;">
                <i class="lucide-save"></i> Применить изменения
            </button>
        </div>
    </form>

    <div style="margin-top:48px; border-top: 1px solid var(--win-border); pt: 32px;">
        <h2 style="font-size:24px; margin-bottom:24px;">Обслуживание и данные</h2>

        <div class="form-grid">
            <section class="card mica">
                <h3 style="margin-top:0;">Резервное копирование</h3>
                <p style="font-size:13px; color:var(--win-text-secondary); margin-bottom:20px;">
                    Создайте полную копию всех данных системы (пользователи, заявки, настройки и файлы) в формате ZIP.
                </p>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="settings.php?action=backup" class="btn-primary" style="display:flex; align-items:center; justify-content:center; gap:8px; text-decoration:none; background: var(--win-accent);">
                        <i class="lucide-download"></i> Скачать архив данных
                    </a>
                </div>

                <div style="margin: 24px 0; height: 1px; background: var(--win-border);"></div>

                <h3>Восстановление</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="maintenance" value="restore">
                    <div class="form-group">
                        <label>Выберите ZIP-архив</label>
                        <input type="file" name="backup_file" accept=".zip" required>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%; background:var(--status-checking);">
                        <i class="lucide-upload"></i> Загрузить и восстановить
                    </button>
                </form>
            </section>

            <section class="card mica">
                <h3 style="margin-top:0;">Тестовая среда</h3>
                <p style="font-size:13px; color:var(--win-text-secondary); margin-bottom:20px;">
                    Наполните систему сгенерированными данными для обучения персонала или тестирования функционала.
                </p>
                <a href="settings.php?action=demo" class="btn-primary" style="display:flex; align-items:center; justify-content:center; gap:8px; text-decoration:none; background:var(--status-working);">
                    <i class="lucide-flask-conical"></i> Генерировать демо-данные
                </a>

                <div style="margin: 24px 0; height: 1px; background: var(--win-border);"></div>

                <h3 style="color:var(--priority-critical);">Сброс системы</h3>
                <p style="font-size:13px; color:var(--win-text-secondary); margin-bottom:20px;">
                    Удаление всех транзакционных данных. Параметры конфигурации и административные аккаунты сохранятся.
                </p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="maintenance" value="reset">
                    <div class="form-group">
                        <label>Пароль подтверждения</label>
                        <input type="password" name="reset_password" placeholder="Введите '12345'" required>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%; background:var(--priority-critical);">
                        <i class="lucide-trash-2"></i> Очистить базу данных
                    </button>
                </form>
            </section>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
