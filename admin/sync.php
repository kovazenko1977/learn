<?php
require_once "auth.php";
require_once "../core/autoload.php";

use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Sync\SyncManager;

$store = new JsonStore(__DIR__ . '/../data');
$sync = new SyncManager($store);

$pageTitle = 'Синхронизация данных';
$successMessage = '';
$errorMessage = '';

$config = $sync->getSyncConfig();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_config') {
        $config['remote_url'] = $_POST['remote_url'] ?? '';
        $config['api_key'] = $_POST['api_key'] ?? '';
        $config['is_local'] = isset($_POST['is_local']);
        if ($sync->saveSyncConfig($config)) {
            $successMessage = "Настройки сохранены!";
        }
    } elseif ($_POST['action'] === 'push') {
        $res = $sync->pushData();
        if ($res['success']) $successMessage = $res['message'];
        else $errorMessage = $res['message'];
    } elseif ($_POST['action'] === 'pull') {
        $res = $sync->pullData();
        if ($res['success']) $successMessage = $res['message'];
        else $errorMessage = $res['message'];
    }
}

include 'includes/header.php';
?>

<div class="grid-2">
    <div class="mica-card">
        <h2>🔄 Статус синхронизации</h2>
        <div style="padding: 20px; background: rgba(0,120,212,0.05); border-radius: 12px; margin-bottom: 20px;">
            <p><strong>Последнее обновление:</strong> <?php echo $config['last_sync'] ?? 'Никогда'; ?></p>
            <p><strong>Режим работы:</strong> <?php echo $config['is_local'] ? '🏠 Локальная версия (ПК)' : '🌐 Облачная версия (Сервер)'; ?></p>
        </div>

        <div style="display: flex; gap: 10px;">
            <form method="POST" style="flex: 1;">
                <input type="hidden" name="action" value="push">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    📤 Отправить данные на сервер
                </button>
            </form>
            <form method="POST" style="flex: 1;">
                <input type="hidden" name="action" value="pull">
                <button type="submit" class="btn btn-outline" style="width: 100%;">
                    📥 Загрузить данные с сервера
                </button>
            </form>
        </div>

        <p style="font-size: 0.8rem; color: #666; margin-top: 15px;">
            * Внимание: При загрузке данные на этом компьютере будут заменены данными с сервера.
            Рекомендуется сделать резервную копию перед началом.
        </p>
    </div>

    <div class="mica-card">
        <h2>⚙️ Настройки подключения</h2>
        <form method="POST">
            <input type="hidden" name="action" value="save_config">
            <div class="form-group">
                <label>URL удаленного сервера</label>
                <input type="url" name="remote_url" value="<?php echo htmlspecialchars($config['remote_url']); ?>" placeholder="https://sanatorium-cloud.ru" style="width: 100%;">
            </div>
            <div class="form-group">
                <label>API Токен (ключ доступа)</label>
                <input type="password" name="api_key" value="<?php echo htmlspecialchars($config['api_key']); ?>" placeholder="Ваш персональный токен" style="width: 100%;">
                <small style="color: #888;">Токен можно скопировать в разделе "Пользователи" -> Редактировать профиль</small>
            </div>
            <div class="form-group" style="margin-top: 15px;">
                <label>
                    <input type="checkbox" name="is_local" <?php echo $config['is_local'] ? 'checked' : ''; ?>>
                    Это локальная копия (установлена на ПК)
                </label>
            </div>
            <button type="submit" class="btn btn-secondary" style="margin-top: 10px;">Сохранить настройки</button>
        </form>
    </div>
</div>

<?php if ($successMessage): ?>
    <div style="background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 8px; margin-top: 20px; border: 1px solid #badbcc;">
        <?php echo $successMessage; ?>
    </div>
<?php endif; ?>

<?php if ($errorMessage): ?>
    <div style="background: #f8d7da; color: #842029; padding: 15px; border-radius: 8px; margin-top: 20px; border: 1px solid #f5c2c7;">
        <?php echo $errorMessage; ?>
    </div>
<?php endif; ?>

<div class="mica-card" style="margin-top: 24px;">
    <h3>📖 Как работает синхронизация?</h3>
    <p style="color: #444; line-height: 1.6;">
        Система позволяет работать одновременно в двух режимах. Вы можете установить wesbooking на компьютер администратора (локально),
        чтобы программа работала мгновенно и независимо от интернета.
        Когда появляется связь, вы нажимаете <strong>"Отправить данные"</strong>, и все новые бронирования улетают на ваш основной сайт.
        <br><br>
        Если директор зашел в программу из дома и внес изменения, администратор на ресепшн нажимает <strong>"Загрузить данные"</strong>,
        и база данных на компьютере обновляется актуальной информацией.
    </p>
</div>

<?php include 'includes/footer.php'; ?>
