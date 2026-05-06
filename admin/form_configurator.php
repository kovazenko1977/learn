<?php require_once __DIR__ . "/auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

$pageTitle = 'Конфигуратор формы бронирования';
include 'includes/header.php';

$configFile = __DIR__ . '/../data/form_config.json';
$config = json_decode(file_get_contents($configFile), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($config['fields'] as $key => &$field) {
        $field['enabled'] = isset($_POST['fields'][$key]['enabled']);
        $field['required'] = isset($_POST['fields'][$key]['required']);
        $field['label'] = $_POST['fields'][$key]['label'] ?? $field['label'];
    }
    $config['general']['title'] = $_POST['general']['title'] ?? 'Онлайн-бронирование';
    $config['general']['allow_hourly'] = isset($_POST['general']['allow_hourly']);

    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "<div class='alert alert-success'>Конфигурация сохранена!</div>";
}
?>

<div class="mica-card">
    <form method="POST" class="modern-form">
        <h3>Общие настройки</h3>
        <div class="form-group">
            <label>Заголовок формы</label>
            <input type="text" name="general[title]" value="<?php echo htmlspecialchars($config['general']['title']); ?>">
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="general[allow_hourly]" <?php echo $config['general']['allow_hourly'] ? 'checked' : ''; ?>> Разрешить почасовое бронирование</label>
        </div>

        <h3>Поля формы</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Поле</th>
                    <th>Название (Label)</th>
                    <th>Включено</th>
                    <th>Обязательно</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($config['fields'] as $key => $f): ?>
                <tr>
                    <td><?php echo $key; ?></td>
                    <td><input type="text" name="fields[<?php echo $key; ?>][label]" value="<?php echo htmlspecialchars($f['label']); ?>" style="width:100%;"></td>
                    <td><input type="checkbox" name="fields[<?php echo $key; ?>][enabled]" <?php echo $f['enabled'] ? 'checked' : ''; ?>></td>
                    <td><input type="checkbox" name="fields[<?php echo $key; ?>][required]" <?php echo $f['required'] ? 'checked' : ''; ?>></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Сохранить настройки</button>
        </div>
    </form>
</div>

<div class="mica-card" style="margin-top:20px;">
    <h3>Шорт-код для вставки на сайт</h3>
    <p>Скопируйте этот код и вставьте в нужное место на вашем сайте:</p>
    <pre style="background:#eee; padding:10px; border-radius:5px;">&lt;script src="https://<?php echo $_SERVER['HTTP_HOST']; ?>/embed/booking-loader.js" data-api-url="https://<?php echo $_SERVER['HTTP_HOST']; ?>/api/v1.php"&gt;&lt;/script&gt;</pre>
</div>

<?php include 'includes/footer.php'; ?>
