<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

$pageTitle = 'Состояние системы';

$action = $_POST['action'] ?? '';
$fixMessage = '';

if ($action === 'fix_all') {
    $root = __DIR__ . '/../';
    $dataDir = $root . 'data';

    // 1. Ensure data directory exists
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    // 2. Ensure .htaccess in data exists
    $htaccess = $dataDir . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Deny from all");
    }

    // 3. Ensure mandatory files exist
    $mandatoryFiles = [
        'rooms.json' => '[]',
        'bookings.json' => '[]',
        'guests.json' => '[]',
        'settings.json' => json_encode([
            'org_name' => 'Sanatorium Pro',
            'auth_enabled' => false,
            'calendar_colors' => [
                'free' => '#ffffff',
                'reserved' => '#fff3cd',
                'partial_male' => '#e0f2fe',
                'partial_female' => '#fce7f3',
                'full' => '#fee2e2'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ];

    foreach ($mandatoryFiles as $file => $defaultContent) {
        $path = $dataDir . '/' . $file;
        if (!file_exists($path)) {
            file_put_contents($path, $defaultContent);
        }
    }

    $fixMessage = "Все обнаруженные проблемы были автоматически исправлены!";
}

include 'includes/header.php';

$checks = [];

// 1. PHP Version
$checks[] = [
    'title' => 'Версия PHP',
    'status' => (version_compare(PHP_VERSION, '8.0.0') >= 0),
    'message' => 'Текущая версия: ' . PHP_VERSION . '. Требуется >= 8.0.0',
    'critical' => true
];

// 2. Data Writable
$dataDir = __DIR__ . '/../data';
$checks[] = [
    'title' => 'Права записи (Data)',
    'status' => is_writable($dataDir),
    'message' => is_writable($dataDir) ? 'Папка данных доступна для записи.' : 'Папка /data не доступна для записи!',
    'critical' => true
];

// 3. Database Consistency
$tables = ['rooms', 'bookings', 'guests', 'packages', 'procedures', 'extra_services', 'settings'];
foreach($tables as $table) {
    $path = __DIR__ . '/../data/' . $table . '.json';
    $exists = file_exists($path);
    $checks[] = [
        'title' => "Файл базы: $table.json",
        'status' => $exists,
        'message' => $exists ? 'Файл найден. Записей: ' . count($store->findAll($table)) : 'Файл ОТСУТСТВУЕТ!',
        'critical' => in_array($table, ['rooms', 'bookings', 'guests', 'settings'])
    ];
}

// 4. Security Check (.htaccess)
$htaccessPath = __DIR__ . '/../data/.htaccess';
$checks[] = [
    'title' => 'Безопасность (data/.htaccess)',
    'status' => file_exists($htaccessPath),
    'message' => file_exists($htaccessPath) ? 'Файл защиты найден.' : 'Файл .htaccess отсутствует! Данные могут быть доступны извне!',
    'critical' => true
];

// 5. PWA Files Check
$root = __DIR__ . '/../';
$manifestPath = $root . 'manifest.json';
$swPath = $root . 'sw.js';

$checks[] = [
    'title' => 'PWA: Manifest',
    'status' => file_exists($manifestPath),
    'message' => file_exists($manifestPath) ? 'Файл manifest.json найден.' : 'Файл manifest.json ОТСУТСТВУЕТ!',
    'critical' => false
];

$checks[] = [
    'title' => 'PWA: Service Worker',
    'status' => file_exists($swPath),
    'message' => file_exists($swPath) ? 'Файл sw.js найден.' : 'Файл sw.js ОТСУТСТВУЕТ!',
    'critical' => false
];

$hasCriticalErrors = false;
foreach($checks as $c) {
    if (!$c['status'] && $c['critical']) {
        $hasCriticalErrors = true;
        break;
    }
}
?>

<div class="mica-card">
    <h2>🩺 Проверка здоровья системы</h2>

    <?php if ($fixMessage): ?>
        <div class="alert alert-success" style="margin-bottom: 20px; padding: 15px; background: rgba(16, 124, 16, 0.1); color: #107c10; border-radius: 8px;">
            <?php echo $fixMessage; ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 20px;">
        <?php foreach ($checks as $c): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(0,0,0,0.02); border-radius: 8px; border-left: 4px solid <?php echo $c['status'] ? '#107c10' : ($c['critical'] ? '#d13438' : '#ffaa44'); ?>;">
                <div>
                    <strong style="display: block;"><?php echo $c['title']; ?> <?php if($c['critical']) echo '<span style="color:#d13438; font-size: 0.7rem;">(КРИТИЧНО)</span>'; ?></strong>
                    <span style="font-size: 0.85rem; color: #666;"><?php echo $c['message']; ?></span>
                </div>
                <div style="font-size: 1.2rem;">
                    <?php echo $c['status'] ? '✅' : ($c['critical'] ? '❌' : '⚠️'); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="margin-top: 30px; display: flex; gap: 15px;">
        <a href="settings.php" class="btn btn-secondary">Вернуться в настройки</a>

        <?php if ($hasCriticalErrors): ?>
            <form method="POST">
                <input type="hidden" name="action" value="fix_all">
                <button type="submit" class="btn btn-primary" style="background: #107c10; border-color: #107c10;">
                    Исправить все ошибки автоматически
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
