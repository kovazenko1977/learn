<?php
namespace App\Database;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;
    private string $dbPath;

    public function __construct(?string $dbPath = null) {
        if ($dbPath === null) {
            $this->dbPath = dirname(__DIR__, 2) . '/data/labelpro.sqlite';
        } else {
            $this->dbPath = $dbPath;
        }
    }

    public function getConnection(): PDO {
        if (self::$instance === null) {
            $dir = dirname($this->dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            try {
                self::$instance = new PDO("sqlite:" . $this->dbPath);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $this->initializeSchema();
            } catch (PDOException $e) {
                throw new PDOException("Connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    private function initializeSchema(): void {
        $sql = "
            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE
            );
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                composition TEXT,
                gost TEXT,
                manufacturer TEXT,
                volume TEXT,
                alcohol TEXT,
                sugar TEXT,
                expiration TEXT,
                barcode TEXT,
                category_id INTEGER,
                extra_data TEXT,
                FOREIGN KEY (category_id) REFERENCES categories(id)
            );
            CREATE TABLE IF NOT EXISTS templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                category_id INTEGER,
                width REAL NOT NULL,
                height REAL NOT NULL,
                unit TEXT DEFAULT 'mm',
                json_data TEXT NOT NULL,
                FOREIGN KEY (category_id) REFERENCES categories(id)
            );
            INSERT OR IGNORE INTO categories (name) VALUES
            ('Кеги'), ('Коробки'), ('Алкоголь'), ('Транспортная тара'), ('Паллеты'), ('Бутылки'), ('Пищевая продукция');
        ";
        self::$instance->exec($sql);
    }
}
