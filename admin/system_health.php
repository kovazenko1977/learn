<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

$pageTitle = 'Состояние системы';
include 'includes/header.php';

$checks = [];

// 1. PHP Version
$checks[] = [
    'title' => 'Версия PHP',
    'status' => (version_compare(PHP_VERSION, '8.0.0') >= 0),
    'message' => 'Текущая версия: ' . PHP_VERSION . '. Требуется >= 8.0.0'
];

// 2. Data Writable
$dataDir = __DIR__ . '/../data';
$checks[] = [
    'title' => 'Права записи (Data)',
    'status' => is_writable($dataDir),
    'message' => is_writable($dataDir) ? 'Папка данных доступна для записи.' : 'Папка /data не доступна для записи!'
];

// 3. Database Consistency
$tables = ['rooms', 'bookings', 'guests', 'packages', 'procedures', 'extra_services'];
foreach($tables as $table) {
    $data = $store->findAll($table);
    $checks[] = [
        'title' => "Таблица: $table",
        'status' => is_array($data),
        'message' => is_array($data) ? 'Записей: ' . count($data) : 'Ошибка чтения файла таблицы!'
    ];
}

// 4. Overlap Check (Basic)
$bookings = $store->findAll('bookings');
$overlaps = 0;
// Note: Very basic check for identical times in the same room
$seen = [];
foreach($bookings as $b) {
    $key = ($b['room_id'] ?? 0) . $b['check_in'] . $b['check_out'];
    if (isset($seen[$key])) $overlaps++;
    $seen[$key] = true;
}
$checks[] = [
    'title' => 'Проверка дубликатов броней',
    'status' => ($overlaps === 0),
    'message' => ($overlaps === 0) ? 'Дубликатов не обнаружено.' : "Найдено дублей: $overlaps"
];

// 5. PWA Files Check
$root = __DIR__ . '/../';
$manifestPath = $root . 'manifest.json';
$swPath = $root . 'sw.js';

$checks[] = [
    'title' => 'PWA: Manifest',
    'status' => file_exists($manifestPath),
    'message' => file_exists($manifestPath) ? 'Файл manifest.json найден.' : 'Файл manifest.json ОТСУТСТВУЕТ!'
];

$checks[] = [
    'title' => 'PWA: Service Worker',
    'status' => file_exists($swPath),
    'message' => file_exists($swPath) ? 'Файл sw.js найден.' : 'Файл sw.js ОТСУТСТВУЕТ!'
];

?>

<div class="mica-card">
    <h2>🩺 Проверка здоровья системы</h2>
    <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 20px;">
        <?php foreach ($checks as $c): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(0,0,0,0.02); border-radius: 8px; border-left: 4px solid <?php echo $c['status'] ? '#107c10' : '#d83b01'; ?>;">
                <div>
                    <strong style="display: block;"><?php echo $c['title']; ?></strong>
                    <span style="font-size: 0.85rem; color: #666;"><?php echo $c['message']; ?></span>
                </div>
                <div style="font-size: 1.2rem;">
                    <?php echo $c['status'] ? '✅' : '❌'; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="margin-top: 30px;">
        <a href="settings.php" class="btn btn-secondary">Вернуться в настройки</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
