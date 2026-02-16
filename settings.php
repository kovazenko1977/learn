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
$alertType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    $action = $_POST['action'] ?? 'save_settings';

    if ($action === 'save_settings') {
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
            $alertType = 'danger';
        }
    } elseif ($action === 'reset_all') {
        if ($_POST['password'] === '12345') {
            $files = ['requests.json', 'users.json', 'services.json', 'templates.json', 'logs/notifications.json'];
            foreach ($files as $f) {
                $store = new JsonStore('data/' . $f);
                $store->save([]);
            }
            // Re-init admin
            $userStore = new JsonStore('data/users.json');
            $userStore->save([['id' => 1, 'name' => 'Администратор', 'role' => 'admin', 'code' => '0000', 'service_id' => null]]);
            $message = 'Все данные удалены. Система сброшена.';
        } else {
            $message = 'Неверный пароль!';
            $alertType = 'danger';
        }
    } elseif ($action === 'load_demo') {
        $loader = new DemoDataLoader();
        $loader->load(
            new JsonStore('data/services.json'),
            new JsonStore('data/users.json'),
            new JsonStore('data/requests.json'),
            new JsonStore('data/templates.json')
        );
        $message = 'Демо-данные успешно загружены';
    } elseif ($action === 'backup') {
        $backup = new BackupManager('data/');
        $file = $backup->createBackup();
        if ($file) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="hop_backup_' . date('Ymd_His') . '.zip"');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            unlink($file);
            exit;
        }
    } elseif ($action === 'restore') {
        if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
            $backup = new BackupManager('data/');
            if ($backup->restoreBackup($_FILES['backup_file']['tmp_name'])) {
                $message = 'Данные успешно восстановлены из архива';
                $settings = $settingsStore->read(); // Reload settings
            } else {
                $message = 'Ошибка при восстановлении архива';
                $alertType = 'danger';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Настройки системы</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert" style="padding:12px; background:<?php echo $alertType === 'success' ? 'var(--status-completed)' : 'var(--status-returned)'; ?>; color:#fff; border-radius:8px; margin-bottom:16px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="action" value="save_settings">

        <section class="card">
            <h2>Общие настройки</h2>
            <div class="form-group">
                <label>Название учреждения</label>
                <input type="text" name="hospital_name" value="<?php echo htmlspecialchars($settings['hospital_name'] ?? ''); ?>" required>
            </div>
        </section>

        <section class="card">
            <h2>Настройка SLA (в часах)</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Низкий приоритет</label>
                    <input type="number" name="sla_low" value="<?php echo $settings['sla']['low'] ?? 72; ?>" required>
                </div>
                <div class="form-group">
                    <label>Средний приоритет</label>
                    <input type="number" name="sla_medium" value="<?php echo $settings['sla']['medium'] ?? 24; ?>" required>
                </div>
                <div class="form-group">
                    <label>Высокий приоритет</label>
                    <input type="number" name="sla_high" value="<?php echo $settings['sla']['high'] ?? 4; ?>" required>
                </div>
                <div class="form-group">
                    <label>Критический приоритет</label>
                    <input type="number" name="sla_critical" value="<?php echo $settings['sla']['critical'] ?? 1; ?>" required>
                </div>
            </div>
        </section>

        <section class="card">
            <h2>Интеграция Telegram</h2>
            <div class="form-group">
                <label>Telegram Bot Token</label>
                <input type="text" name="telegram_token" value="<?php echo htmlspecialchars($settings['telegram_token'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Telegram Chat ID</label>
                <input type="text" name="telegram_chat_id" value="<?php echo htmlspecialchars($settings['telegram_chat_id'] ?? ''); ?>">
            </div>
        </section>

        <button type="submit" class="btn-primary">Сохранить настройки</button>
    </form>

    <div class="form-grid" style="margin-top:24px;">
        <section class="card">
            <h2>Резервное копирование</h2>
            <p style="font-size:14px; color:var(--win-text-secondary); margin-bottom:16px;">
                Сохраните все данные (заявки, пользователи, настройки) в один архив.
            </p>
            <form method="POST" style="margin-bottom:16px;">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="backup">
                <button type="submit" class="btn-primary" style="background:var(--win-text-secondary);">Скачать архив (.zip)</button>
            </form>

            <hr style="border:0; border-top:1px solid var(--win-border); margin:16px 0;">

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="restore">
                <div class="form-group">
                    <label>Восстановить из архива</label>
                    <input type="file" name="backup_file" accept=".zip" required>
                </div>
                <button type="submit" class="btn-primary" style="background:var(--status-checking);" onclick="return confirm('Все текущие данные будут заменены данными из архива. Продолжить?')">Восстановить</button>
            </form>
        </section>

        <section class="card">
            <h2>Управление данными</h2>
            <form method="POST" style="margin-bottom:16px;">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="load_demo">
                <p style="font-size:14px; color:var(--win-text-secondary); margin-bottom:12px;">Заполнить систему тестовыми данными для ознакомления.</p>
                <button type="submit" class="btn-primary" style="background:var(--win-accent);">Загрузить демо-данные</button>
            </form>

            <hr style="border:0; border-top:1px solid var(--win-border); margin:16px 0;">

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="reset_all">
                <p style="font-size:14px; color:var(--win-text-secondary); margin-bottom:12px;">Полная очистка всех таблиц. Это действие необратимо!</p>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Введите пароль для сброса" required>
                </div>
                <button type="submit" class="btn-primary" style="background:var(--status-returned);" onclick="return confirm('Вы уверены, что хотите удалить ВСЕ данные?')">Удалить все данные</button>
            </form>
        </section>
    </div>

    <section class="card mica" style="text-align:center; margin-top:24px;">
        <h2>О приложении</h2>
        <p>Система управления хозяйственно-оперативными поручениями «ХОП» v1.0</p>
        <p style="font-weight:700; color:var(--win-accent);">Разработчик: WES.BY</p>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
