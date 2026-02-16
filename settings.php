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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $message = 'Настройки сохранены';
    } else {
        $message = 'Ошибка при сохранении';
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
    if ($_POST['maintenance'] === 'restore' && isset($_FILES['backup_file'])) {
        if ($backupManager->restoreBackup($_FILES['backup_file']['tmp_name'])) {
            $message = 'Данные успешно восстановлены';
        } else {
            $message = 'Ошибка при восстановлении';
        }
    } elseif ($_POST['maintenance'] === 'reset') {
        if ($backupManager->resetData($_POST['reset_password'])) {
            $message = 'Все данные успешно удалены';
        } else {
            $message = 'Неверный пароль для сброса';
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'demo_ok') {
    $message = 'Демо-данные успешно загружены (120 заявок)';
}

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Настройки системы</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="padding:12px; background:var(--status-completed); color:#fff; border-radius:8px; margin-bottom:16px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <section class="card">
            <h2>Общие настройки</h2>
            <div class="form-group">
                <label>Название учреждения</label>
                <input type="text" name="hospital_name" value="<?php echo htmlspecialchars($settings['hospital_name']); ?>" required>
            </div>
        </section>

        <section class="card">
            <h2>Настройка SLA (в часах)</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Низкий приоритет</label>
                    <input type="number" name="sla_low" value="<?php echo $settings['sla']['low']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Средний приоритет</label>
                    <input type="number" name="sla_medium" value="<?php echo $settings['sla']['medium']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Высокий приоритет</label>
                    <input type="number" name="sla_high" value="<?php echo $settings['sla']['high']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Критический приоритет</label>
                    <input type="number" name="sla_critical" value="<?php echo $settings['sla']['critical']; ?>" required>
                </div>
            </div>
        </section>

        <section class="card">
            <h2>Интеграция Telegram</h2>
            <div class="form-group">
                <label>Telegram Bot Token</label>
                <input type="text" name="telegram_token" value="<?php echo htmlspecialchars($settings['telegram_token']); ?>">
            </div>
            <div class="form-group">
                <label>Telegram Chat ID</label>
                <input type="text" name="telegram_chat_id" value="<?php echo htmlspecialchars($settings['telegram_chat_id']); ?>">
            </div>
        </section>

        <button type="submit" class="btn-primary">Сохранить настройки</button>
    </form>

    <div style="margin-top:40px;">
        <h1>Обслуживание системы</h1>

        <div class="form-grid">
            <section class="card">
                <h2>Резервное копирование</h2>
                <p style="font-size:14px; color:var(--win-text-secondary); margin-bottom:16px;">
                    Скачать ZIP-архив со всеми данными и загруженными файлами.
                </p>
                <a href="settings.php?action=backup" class="btn-primary" style="display:inline-block; text-decoration:none; text-align:center;">
                    Скачать бэкап
                </a>

                <hr style="margin:24px 0; border:0; border-top:1px solid var(--win-border);">

                <h2>Восстановление</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="maintenance" value="restore">
                    <div class="form-group">
                        <label>Файл бэкапа (.zip)</label>
                        <input type="file" name="backup_file" accept=".zip" required>
                    </div>
                    <button type="submit" class="btn-primary" style="background:var(--status-checking);">Восстановить из файла</button>
                </form>
            </section>

            <section class="card">
                <h2>Демо-данные</h2>
                <p style="font-size:14px; color:var(--win-text-secondary); margin-bottom:16px;">
                    Заполнить систему тестовыми данными (30 пользователей, 120 заявок).
                </p>
                <a href="settings.php?action=demo" class="btn-primary" style="display:inline-block; text-decoration:none; text-align:center; background:var(--status-working);">
                    Генерировать демо-данные
                </a>

                <hr style="margin:24px 0; border:0; border-top:1px solid var(--win-border);">

                <h2 style="color:var(--priority-critical);">Сброс всех данных</h2>
                <p style="font-size:14px; color:var(--win-text-secondary); margin-bottom:16px;">
                    Внимание! Это действие удалит все заявки и уведомления.
                </p>
                <form method="POST">
                    <input type="hidden" name="maintenance" value="reset">
                    <div class="form-group">
                        <label>Пароль подтверждения (12345)</label>
                        <input type="password" name="reset_password" required>
                    </div>
                    <button type="submit" class="btn-primary" style="background:var(--priority-critical);">УДАЛИТЬ ВСЕ ДАННЫЕ</button>
                </form>
            </section>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
