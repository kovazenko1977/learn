<?php
// api/config.php

define('BASE_DIR', dirname(__DIR__));
define('DATA_DIR', BASE_DIR . '/data');
define('UPLOADS_DIR', BASE_DIR . '/uploads');
define('BACKUP_DIR', DATA_DIR . '/backups');
define('JWT_SECRET', 'flower_studio_pro_jwt_secret_key_2025_#9988');
define('JWT_EXPIRATION', 30 * 86400); // 30 days in seconds

// Ensure directory structure
if (!file_exists(DATA_DIR)) {
    @mkdir(DATA_DIR, 0777, true);
}
if (!file_exists(UPLOADS_DIR)) {
    @mkdir(UPLOADS_DIR, 0777, true);
}
if (!file_exists(BACKUP_DIR)) {
    @mkdir(BACKUP_DIR, 0777, true);
}
