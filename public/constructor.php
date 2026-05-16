<?php require_once __DIR__ . "/auth.php";
require_once __DIR__ . '/../src/autoload.php';
use App\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

$classes = $store->findAll('room_classes');

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
        $config['general']['title'] = $_POST['rooms']['title'] ?? 'Бронирование номеров';
    }

    if (isset($_POST['sauna'])) {
        if (!isset($config['sauna'])) $config['sauna'] = ['fields' => [], 'title' => 'Почасовое бронирование'];
        $config['sauna']['title'] = $_POST['sauna']['title'];
        foreach (['client_name', 'phone', 'persons'] as $key) {
            $config['sauna']['fields'][$key]['enabled'] = isset($_POST['sauna']['fields'][$key]['enabled']);
            $config['sauna']['fields'][$key]['required'] = isset($_POST['sauna']['fields'][$key]['required']);
            $config['sauna']['fields'][$key]['label'] = $_POST['sauna']['fields'][$key]['label'] ?? $key;
        }
    }

    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "<div class='alert alert-success'>Настройки форм сохранены!</div>";
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_url = "$protocol://$host";
?>

<div class="grid-2">
    <div class="mica-card">
        <h3>🏠 Настройка полей (Посуточно)</h3>
        <form method="POST" class="modern-form">
            <input type="hidden" name="rooms" value="1">
            <div class="form-group">
                <label>Заголовок формы</label>
                <input type="text" name="rooms[title]" value="<?php echo htmlspecialchars($config['general']['title']); ?>">
            </div>

            <table class="table" style="font-size: 0.8rem;">
                <thead>
                    <tr><th>Поле</th><th>Имя (Метка)</th><th>Вкл</th><th>Обяз</th></tr>
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
            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:15px;">Сохранить поля посуточных объектов</button>
        </form>
    </div>

    <div class="mica-card">
        <h3>🧖‍♀️ Настройка полей (Почасово)</h3>
        <form method="POST" class="modern-form">
            <input type="hidden" name="sauna" value="1">
            <div class="form-group">
                <label>Заголовок формы</label>
                <input type="text" name="sauna[title]" value="<?php echo htmlspecialchars($config['sauna']['title'] ?? 'Почасовое бронирование'); ?>">
            </div>

            <table class="table" style="font-size: 0.8rem;">
                <thead>
                    <tr><th>Поле</th><th>Имя (Метка)</th><th>Вкл</th><th>Обяз</th></tr>
                </thead>
                <tbody>
                    <?php
                    $saunaFields = ['client_name', 'phone', 'persons'];
                    foreach ($saunaFields as $key):
                        $f = $config['sauna']['fields'][$key] ?? ['enabled' => true, 'required' => true, 'label' => $key];
                    ?>
                    <tr>
                        <td><?php echo $key; ?></td>
                        <td><input type="text" name="sauna[fields][<?php echo $key; ?>][label]" value="<?php echo htmlspecialchars($f['label']); ?>" style="width:100%;"></td>
                        <td><input type="checkbox" name="sauna[fields][<?php echo $key; ?>][enabled]" <?php echo $f['enabled'] ? 'checked' : ''; ?>></td>
                        <td><input type="checkbox" name="sauna[fields][<?php echo $key; ?>][required]" <?php echo $f['required'] ? 'checked' : ''; ?>></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:15px;">Сохранить поля почасовых объектов</button>
        </form>
    </div>
</div>

<div class="mica-card" style="margin-top:20px;">
    <h3>🔗 Способы интеграции</h3>
    <p>Выберите наиболее подходящий способ добавления бронирования на ваш сайт.</p>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Тип объекта</th>
                    <th>Прямая ссылка</th>
                    <th>Iframe (простой)</th>
                    <th>Шорт-код (JS Loader)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($classes as $c): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                    <td>
                        <a href="<?php echo $base_url; ?>/booking.php?type_id=<?php echo $c['id']; ?>" target="_blank" class="badge badge-outline">
                            Открыть ↗
                        </a>
                    </td>
                    <td>
                        <textarea style="font-size:0.6rem; width:100%; height:40px; background:#f8fafc;" readonly>&lt;iframe src="<?php echo $base_url; ?>/booking.php?type_id=<?php echo $c['id']; ?>" width="100%" height="900" frameborder="0"&gt;&lt;/iframe&gt;</textarea>
                    </td>
                    <td>
                        <textarea style="font-size:0.6rem; width:100%; height:60px; background:#f8fafc;" readonly>&lt;div id="booking-<?php echo $c['id']; ?>"&gt;&lt;/div&gt;
&lt;script src="<?php echo $base_url; ?>/embed/booking-loader.js" data-container="booking-<?php echo $c['id']; ?>" data-type-id="<?php echo $c['id']; ?>"&gt;&lt;/script&gt;</textarea>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
