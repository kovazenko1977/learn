<?php

class Storage {
    private static ?PDO $pdo = null;

    public static function getPDO(): PDO {
        if (self::$pdo === null) {
            $dbDir = __DIR__ . '/../data';
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            $dbFile = $dbDir . '/memorial.sqlite';
            self::$pdo = new PDO('sqlite:' . $dbFile, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            self::initTables();
        }
        return self::$pdo;
    }

    private static function initTables(): void {
        $pdo = self::$pdo;

        // Users table (phone login)
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            phone TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            full_name TEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Admin users table
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            login TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Seed default admin if missing (login: 12345, password: 12345)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $defaultLogin = '12345';
            $defaultPasswordHash = password_hash('12345', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (login, password_hash) VALUES (?, ?)");
            $stmt->execute([$defaultLogin, $defaultPasswordHash]);
        }

        // Memorial pages table
        $pdo->exec("CREATE TABLE IF NOT EXISTS pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT UNIQUE NOT NULL,
            user_id INTEGER NOT NULL,
            full_name TEXT NOT NULL,
            birth_date TEXT,
            death_date TEXT,
            photo TEXT DEFAULT '',
            audio_path TEXT DEFAULT '',
            epitaph TEXT DEFAULT '',
            biography TEXT DEFAULT '',
            cemetery TEXT DEFAULT '',
            section TEXT DEFAULT '',
            grave_num TEXT DEFAULT '',
            latitude REAL DEFAULT 0,
            longitude REAL DEFAULT 0,
            status TEXT DEFAULT 'pending',
            rejection_reason TEXT DEFAULT '',
            views INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // Ensure audio_path column exists if table was created previously
        try {
            $pdo->exec("ALTER TABLE pages ADD COLUMN audio_path TEXT DEFAULT ''");
        } catch (Exception $e) {
            // Column already exists
        }

        // Family Links Table (Connecting related deceased members)
        $pdo->exec("CREATE TABLE IF NOT EXISTS family_links (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_id INTEGER NOT NULL,
            related_page_id INTEGER NOT NULL,
            relation_title TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
            FOREIGN KEY (related_page_id) REFERENCES pages(id) ON DELETE CASCADE
        )");

        // Relatives contact information
        $pdo->exec("CREATE TABLE IF NOT EXISTS relatives (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_id INTEGER NOT NULL,
            relation_type TEXT DEFAULT '',
            name TEXT NOT NULL,
            phone TEXT DEFAULT '',
            email TEXT DEFAULT '',
            is_public INTEGER DEFAULT 1,
            FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
        )");

        // Condolences / Memories
        $pdo->exec("CREATE TABLE IF NOT EXISTS condolences (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_id INTEGER NOT NULL,
            author_name TEXT NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
        )");

        // Virtual candles
        $pdo->exec("CREATE TABLE IF NOT EXISTS candles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_id INTEGER NOT NULL,
            author_name TEXT DEFAULT 'Гость',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
        )");

        // Admin notifications
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_id INTEGER NOT NULL,
            message TEXT NOT NULL,
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
        )");

        // Additional Photos Table (up to 10 photos per memorial page)
        $pdo->exec("CREATE TABLE IF NOT EXISTS page_photos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_id INTEGER NOT NULL,
            photo_path TEXT NOT NULL,
            sort_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
        )");

        // System Settings
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
        )");

        // Default settings
        $defaultSettings = [
            'site_title' => 'Память - Книга Памяти и Захоронений',
            'auto_approve' => '0',
            'contact_email' => 'admin@memory-site.ru',
            'custom_notice' => 'Страница памяти с QR-кодом для мемориалов.'
        ];

        foreach ($defaultSettings as $k => $v) {
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)");
            $stmt->execute([$k, $v]);
        }
    }
}
