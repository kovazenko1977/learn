<?php require_once __DIR__ . "/auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

$pageTitle = 'Конструктор форм бронирования';
include 'includes/header.php';

$configFile = __DIR__ . '/../data/form_config.json';
$config = json_decode(file_get_contents($configFile), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['rooms'])) {
        foreach ($config['fields'] as $key => &$field) {
            $field['enabled'] = isset($_POST['rooms']['fields'][$key]['enabled']);
            $field['required'] = isset($_POST['rooms']['fields'][$key]['required']);
            $field['label'] = $_POST['rooms']['fields'][$key]['label'] ?? $field['label'];
        }
        $config['general']['title'] = $_POST['rooms']['title'] ?? 'Онлайн-бронирование';
    }

    if (isset($_POST['sauna'])) {
        if (!isset($config['sauna'])) $config['sauna'] = ['fields' => [], 'title' => 'Бронирование Сауны'];
        $config['sauna']['title'] = $_POST['sauna']['title'];
        // Sauna fields logic could be added here if needed to be separate
    }

    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "<div class='alert alert-success'>Конструктор обновлен!</div>";
}
?>

<div class="grid-2">
    <div class="mica-card">
        <h3>🏠 Форма для номеров</h3>
        <form method="POST" class="modern-form">
            <input type="hidden" name="rooms" value="1">
            <div class="form-group">
                <label>Заголовок</label>
                <input type="text" name="rooms[title]" value="<?php echo htmlspecialchars($config['general']['title']); ?>">
            </div>

            <table class="table" style="font-size: 0.8rem;">
                <thead>
                    <tr><th>Поле</th><th>Имя</th><th>Вкл</th><th>Обяз</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($config['fields'] as $key => $f): ?>
                    <tr>
                        <td><?php echo $key; ?></td>
                        <td><input type="text" name="rooms[fields][<?php echo $key; ?>][label]" value="<?php echo htmlspecialchars($f['label']); ?>" style="width:100%;"></td>
                        <td><input type="checkbox" name="rooms[fields][<?php echo $key; ?>][enabled]" <?php echo $f['enabled'] ? 'checked' : ''; ?>></td>
                        <td><input type="checkbox" name="rooms[fields][<?php echo $key; ?>][required]" <?php echo $f['required'] ? 'checked' : ''; ?>></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:15px;">Сохранить форму номеров</button>
        </form>
    </div>

    <div class="mica-card">
        <h3>🧖‍♀️ Форма для сауны</h3>
        <form method="POST" class="modern-form">
            <input type="hidden" name="sauna" value="1">
            <div class="form-group">
                <label>Заголовок</label>
                <input type="text" name="sauna[title]" value="<?php echo htmlspecialchars($config['sauna']['title'] ?? 'Бронирование Сауны'); ?>">
            </div>
            <p style="font-size: 0.85rem; color: #666;">Почасовое бронирование всегда включено для ресурсов типа «Сауна».</p>
            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:15px;">Сохранить форму сауны</button>
        </form>
    </div>
</div>

<div class="mica-card" style="margin-top:20px;">
    <h3>🔗 Шорт-коды для сайта</h3>
    <p>Добавьте эти скрипты в любое место на вашем сайте:</p>

    <div class="grid-2">
        <div>
            <strong>Для бронирования номеров:</strong>
            <pre style="background:#f1f5f9; padding:10px; border-radius:8px; font-size:0.7rem; overflow-x:auto;">&lt;div id="booking-rooms"&gt;&lt;/div&gt;
&lt;script src="https://<?php echo $_SERVER['HTTP_HOST']; ?>/embed/booking-loader.js"
        data-container="booking-rooms"
        data-type="rooms"&gt;&lt;/script&gt;</pre>
        </div>
        <div>
            <strong>Для бронирования сауны:</strong>
            <pre style="background:#f1f5f9; padding:10px; border-radius:8px; font-size:0.7rem; overflow-x:auto;">&lt;div id="booking-sauna"&gt;&lt;/div&gt;
&lt;script src="https://<?php echo $_SERVER['HTTP_HOST']; ?>/embed/booking-loader.js"
        data-container="booking-sauna"
        data-type="sauna"&gt;&lt;/script&gt;</pre>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
