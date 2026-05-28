<?php
namespace App\Models;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dbPath = __DIR__ . '/../../../data/vsprint.sqlite';
        $this->pdo = new \PDO("sqlite:$dbPath");
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $this->init();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    private function init() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                role TEXT DEFAULT 'user',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                type TEXT NOT NULL -- 'product' or 'template'
            );

            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER,
                name TEXT NOT NULL,
                composition TEXT,
                gost TEXT,
                manufacturer TEXT,
                volume TEXT,
                alcohol TEXT,
                sugar TEXT,
                expiration TEXT,
                barcode TEXT,
                logo TEXT,
                warnings TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id)
            );

            CREATE TABLE IF NOT EXISTS templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                category TEXT, -- 'kegs', 'boxes', 'alcohol', 'transport', 'pallets'
                width FLOAT NOT NULL,
                height FLOAT NOT NULL,
                data TEXT NOT NULL, -- JSON representation of elements
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS print_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                template_id INTEGER,
                product_id INTEGER,
                quantity INTEGER,
                printed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (template_id) REFERENCES templates(id),
                FOREIGN KEY (product_id) REFERENCES products(id)
            );
        ");

        // Seed default admin if not exists
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $pass = password_hash('admin', PASSWORD_DEFAULT);
            $this->pdo->exec("INSERT INTO users (username, password, role) VALUES ('admin', '$pass', 'admin')");
        }
    }
}
