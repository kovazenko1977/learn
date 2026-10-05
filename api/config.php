<?php
// api/config.php
define('DATA_DIR', __DIR__ . '/../data');
define('UPLOADS_DIR', __DIR__ . '/../uploads');
define('BACKUPS_DIR', DATA_DIR . '/backups');
define('JWT_SECRET', 'flower_studio_pro_super_secret_jwt_key_2025_#9821');
define('JWT_TTL', 30 * 86400); // 30 days in seconds

foreach ([DATA_DIR, UPLOADS_DIR, BACKUPS_DIR] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}
