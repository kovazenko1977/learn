<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;

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
</div>

<?php include 'includes/footer.php'; ?>
