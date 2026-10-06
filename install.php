<?php
// install.php - Automatic Installer for Flower Studio Pro

define('ROOT_DIR', __DIR__);
define('DATA_PATH', ROOT_DIR . '/data');
define('UPLOADS_PATH', ROOT_DIR . '/uploads');
define('BACKUP_PATH', DATA_PATH . '/backups');
define('ICONS_PATH', ROOT_DIR . '/icons');

echo "<h2>Установка Flower Studio Pro PWA...</h2>";

// 1. Create directories
$dirs = [DATA_PATH, UPLOADS_PATH, BACKUP_PATH, ICONS_PATH];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        if (@mkdir($dir, 0777, true)) {
            echo "✔ Создана папка: {$dir}<br>";
        } else {
            echo "❌ Ошибка создания папки: {$dir}<br>";
        }
    }
}

// 2. Generate .htaccess if missing
$htaccessPath = ROOT_DIR . '/.htaccess';
if (!file_exists($htaccessPath)) {
    $htaccessContent = <<<EOT
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^api/(.*)$ api/router.php [QSA,L]

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.html [L]
</IfModule>

<FilesMatch "\.json$">
    Order deny,allow
    Deny from all
</FilesMatch>

<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Frame-Options "SAMEORIGIN"
</IfModule>
EOT;
    file_put_contents($htaccessPath, $htaccessContent);
    echo "✔ Создан файл .htaccess<br>";
}

// 3. Seed initial JSON files if empty
$initialFiles = [
    'users.json' => [
        [
            'id' => 'user_admin_seed',
            'name' => 'Администратор',
            'phone' => '1111',
            'password' => password_hash('1111', PASSWORD_BCRYPT),
            'role' => 'admin',
            'blocked' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ],
    'products.json' => [
        [
            'id' => 'prod_1',
            'title' => 'Розовый каприз',
            'category' => 'Букеты',
            'price' => 85.00,
            'old_price' => 100.00,
            'description' => 'Нежный букет из розовых пионовидных роз и эвкалипта',
            'image' => '🌸',
            'is_sale' => 1,
            'stock' => 4,
            'deleted' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 'prod_2',
            'title' => 'Красная страсть (15 роз)',
            'category' => 'Розы',
            'price' => 120.00,
            'old_price' => null,
            'description' => 'Элитные эквадорские красные розы с бархатными лепестками',
            'image' => '🌹',
            'is_sale' => 0,
            'stock' => 12,
            'deleted' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 'prod_3',
            'title' => 'Весенняя свежесть',
            'category' => 'Композиции',
            'price' => 65.00,
            'old_price' => 75.00,
            'description' => 'Композиция из белых тюльпанов и хризантем в стильной коробке',
            'image' => '💐',
            'is_sale' => 1,
            'stock' => 3,
            'deleted' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 'prod_4',
            'title' => 'Шляпная коробка "Амур"',
            'category' => 'Коробки',
            'price' => 140.00,
            'old_price' => null,
            'description' => 'Премиальные розы в шляпной коробке с атласной лентой',
            'image' => '🎁',
            'is_sale' => 0,
            'stock' => 8,
            'deleted' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ],
    'promos.json' => [
        [
            'id' => 'promo_1',
            'title' => 'Скидка 15% на первый заказ!',
            'description' => 'Используйте промокод FIRST15 при оформлении',
            'image' => '🎉',
            'badge' => 'АКЦИЯ',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ],
    'promocodes.json' => [
        [
            'id' => 'promo_code_1',
            'code' => 'FIRST15',
            'discount_percent' => 15,
            'uses_left' => 100,
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ],
    'news.json' => [
        [
            'id' => 'news_1',
            'title' => 'Поступление свежих пионов!',
            'content' => 'К нам приехала прямая поставка голландских пионов самых редких сортов.',
            'image' => '🌺',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ],
    'settings.json' => [
        [
            'id' => 'setting_default',
            'phone' => '+375 (29) 111-22-33',
            'address' => 'г. Минск, ул. Цветочная, д. 10',
            'working_hours' => '08:00 - 22:00 ежедневно',
            'delivery_cost' => 10.00,
            'free_delivery_threshold' => 100.00,
            'min_order_amount' => 25.00,
            'roulette_discount' => 15,
            'auto_reply_text' => 'Спасибо за ваше сообщение! Наш флорист свяжется с вами в течение 5 минут. 💐',
            'driver' => 'json',
            'mysql_config' => [
                'host' => '127.0.0.1',
                'port' => 3306,
                'dbname' => 'flower_studio',
                'user' => 'root',
                'password' => ''
            ],
            'social_links' => [
                'instagram' => 'https://instagram.com',
                'telegram' => 'https://t.me',
                'viber' => 'viber://chat'
            ],
            'created_at' => date('Y-m-d H:i:s')
        ]
    ],
    'orders.json' => [],
    'chats.json' => [],
    'stats.json' => [],
    'notifications.json' => []
];

foreach ($initialFiles as $filename => $content) {
    $filePath = DATA_PATH . '/' . $filename;
    if (!file_exists($filePath)) {
        file_put_contents($filePath, json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        echo "✔ Инициализирован файл: {$filename}<br>";
    }
}

echo "<br><b>Установка успешно завершена!</b><br>";
echo "Тестовый администратор: телефон <b>1111</b> / пароль <b>1111</b><br>";

// Self-delete
@unlink(__FILE__);
echo "<i>Установочный файл install.php автоматически удален в целях безопасности.</i><br>";
echo "<a href='/'>Перейти в приложение Flower Studio Pro</a>";
