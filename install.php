<?php
/**
 * wesbooking Pro Elite - Автоматический установщик
 */

define('MIN_PHP_VERSION', '8.1.0');

$requirements = [
    'php_version' => [
        'name' => 'Версия PHP (>= ' . MIN_PHP_VERSION . ')',
        'met' => version_compare(PHP_VERSION, MIN_PHP_VERSION, '>='),
        'current' => PHP_VERSION
    ],
    'ext_json' => [
        'name' => 'Расширение JSON',
        'met' => extension_loaded('json'),
    ],
    'ext_fileinfo' => [
        'name' => 'Расширение Fileinfo',
        'met' => extension_loaded('fileinfo'),
    ],
    'ext_mbstring' => [
        'name' => 'Расширение Mbstring',
        'met' => extension_loaded('mbstring'),
    ],
    'dir_data' => [
        'name' => 'Папка data/ (права на запись)',
        'met' => is_writable(__DIR__ . '/data') || (!file_exists(__DIR__ . '/data') && is_writable(__DIR__)),
    ],
    'dir_uploads' => [
        'name' => 'Папка assets/uploads/ (права на запись)',
        'met' => is_writable(__DIR__ . '/assets/uploads') || (!file_exists(__DIR__ . '/assets/uploads') && is_writable(__DIR__ . '/assets')),
    ]
];

$all_met = true;
foreach ($requirements as $req) {
    if (!$req['met']) {
        $all_met = false;
        break;
    }
}

if (isset($_POST['install']) && $all_met) {
    // 1. Создаем структуру папок
    $dirs = [
        'data',
        'data/bookings',
        'data/logs',
        'assets/uploads',
        'assets/uploads/guests',
        'assets/uploads/tasks'
    ];

    foreach ($dirs as $dir) {
        if (!file_exists(__DIR__ . '/' . $dir)) {
            mkdir(__DIR__ . '/' . $dir, 0755, true);
        }
    }

    // 2. Инициализируем настройки по умолчанию, если их нет
    $settingsFile = __DIR__ . '/data/settings.json';
    if (!file_exists($settingsFile)) {
        $defaultSettings = [
            'org_name' => 'Санаторий "Светлый"',
            'currency' => 'RUB',
            'check_in_time' => '14:00',
            'check_out_time' => '12:00',
            'sync_mode' => 'standalone'
        ];
        file_put_contents($settingsFile, json_encode($defaultSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    // 3. Создаем .htaccess в папке data
    $htaccess = "Deny from all";
    file_put_contents(__DIR__ . '/data/.htaccess', $htaccess);

    $success = "Система успешно установлена! Теперь вы можете войти, используя логин 'admin' и пароль 'admin123'. <br><br><b>Важно:</b> Пожалуйста, удалите файл install.php в целях безопасности.";
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Установка wesbooking Pro Elite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .install-card { max-width: 600px; margin: 50px auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .req-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-fail { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-card">
            <h2 class="mb-4">wesbooking <span class="text-primary">Pro Elite</span></h2>
            <p class="text-muted">Мастер автоматической настройки системы</p>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
                <a href="index.php" class="btn btn-primary w-100 mt-3">Перейти к приложению</a>
            <?php else: ?>
                <div class="mb-4">
                    <h5>Проверка системных требований:</h5>
                    <?php foreach ($requirements as $req): ?>
                        <div class="req-item">
                            <span><?php echo $req['name']; ?></span>
                            <span class="<?php echo $req['met'] ? 'status-ok' : 'status-fail'; ?>">
                                <?php echo $req['met'] ? '✓ OK' : '✗ Ошибка'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($all_met): ?>
                    <form method="post">
                        <button type="submit" name="install" class="btn btn-primary w-100 py-3">Начать установку</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-danger">
                        Пожалуйста, исправьте ошибки выше, чтобы продолжить установку.
                    </div>
                    <button class="btn btn-secondary w-100" onclick="window.location.reload()">Проверить снова</button>
                <?php endif; ?>
            <?php endif; ?>

            <div class="mt-4 text-center">
                <small class="text-muted">Developed by Коваженко С.Б. (wes.by)</small>
            </div>
        </div>
    </div>
</body>
</html>