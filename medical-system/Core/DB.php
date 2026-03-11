<?php
namespace Medical\Core;

class DB {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $settings = (new JsonStore('settings'))->getAll();
        $dbHost = $settings['db_host'] ?? 'localhost';
        $dbName = $settings['db_name'] ?? '';
        $dbUser = $settings['db_user'] ?? '';
        $dbPass = $settings['db_pass'] ?? '';

        if (empty($dbName)) {
            throw new \Exception("MySQL Database name is not configured.");
        }

        $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $this->pdo = new \PDO($dsn, $dbUser, $dbPass, $options);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo() {
        return $this->pdo;
    }

    public static function checkTables() {
        try {
            $db = self::getInstance();
            $pdo = $db->getPdo();
            $tables = Schema::getTables();
            $missing = [];

            foreach ($tables as $name => $sql) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$name'");
                if ($stmt->rowCount() == 0) {
                    $missing[] = $name;
                }
            }
            return $missing;
        } catch (\Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    public static function initTables() {
        $db = self::getInstance();
        $pdo = $db->getPdo();
        $tables = Schema::getTables();
        foreach ($tables as $name => $sql) {
            $pdo->exec($sql);
        }
        return true;
    }
}
