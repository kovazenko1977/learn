<?php
// db.php - Database abstraction layer (JSON & MySQL) for CRM

class DB {
    private static $instance = null;
    private $mode = 'json'; // 'json' or 'mysql'
    private $pdo = null;
    private $json_dir = __DIR__ . '/data';

    private function __construct() {
        // Load settings to determine storage mode
        $settings = $this->loadJsonFile('settings.json');
        if (isset($settings['storage_mode'])) {
            $this->mode = $settings['storage_mode'];
        }
        if ($this->mode === 'mysql' && isset($settings['mysql'])) {
            $config = $settings['mysql'];
            try {
                $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
                $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 3 // short timeout
                ]);
            } catch (PDOException $e) {
                // Fallback to json if mysql fails, and log error
                $this->mode = 'json';
                $this->addLog('system', 'system_error', 'database', 'fallback_to_json', 'MySQL connection failed: ' . $e->getMessage());
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getMode() {
        return $this->mode;
    }

    // --- JSON Storage Helpers ---
    private function getJsonPath($file) {
        return $this->json_dir . '/' . $file;
    }

    private function loadJsonFile($file) {
        $path = $this->getJsonPath($file);
        if (!file_exists($path)) {
            return [];
        }
        $fp = fopen($path, 'r');
        if (!$fp) return [];
        flock($fp, LOCK_SH);
        $content = '';
        while (!feof($fp)) {
            $content .= fread($fp, 8192);
        }
        flock($fp, LOCK_UN);
        fclose($fp);
        return json_decode($content, true) ?: [];
    }

    private function saveJsonFile($file, $data) {
        $path = $this->getJsonPath($file);
        $fp = fopen($path, 'w+');
        if (!$fp) return false;
        flock($fp, LOCK_EX);
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        fwrite($fp, $content);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }

    // --- MySQL Schema Auto-Generation ---
    public function setupMySQLSchema($pdoInstance = null) {
        $db = $pdoInstance ?: $this->pdo;
        if (!$db) return false;

        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL,
                full_name VARCHAR(100),
                email VARCHAR(100),
                phone VARCHAR(50),
                notifications_freq VARCHAR(20) DEFAULT 'immediate',
                notify_email TINYINT(1) DEFAULT 1,
                notify_push TINYINT(1) DEFAULT 1,
                notify_tg TINYINT(1) DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS tickets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(100),
                priority VARCHAR(20) DEFAULT 'medium',
                status VARCHAR(20) DEFAULT 'new',
                created_by INT NOT NULL,
                assignee_id INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                sla_deadline DATETIME NULL,
                custom_fields TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ticket_id INT NOT NULL,
                user_id INT NOT NULL,
                comment_text TEXT,
                attachment_path VARCHAR(255) NULL,
                attachment_name VARCHAR(255) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS settings (
                `key` VARCHAR(100) PRIMARY KEY,
                `value` TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(50),
                action VARCHAR(100),
                target_type VARCHAR(50),
                target_id VARCHAR(50),
                details TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];

        foreach ($queries as $q) {
            $db->exec($q);
        }
        return true;
    }

    // --- Switch Storage Mode & Migration ---
    public function switchStorage($newMode, $mysqlConfig = null) {
        $settings = $this->loadJsonFile('settings.json');
        $settings['storage_mode'] = $newMode;
        if ($mysqlConfig !== null) {
            $settings['mysql'] = $mysqlConfig;
        }
        $this->saveJsonFile('settings.json', $settings);

        if ($newMode === 'mysql' && $mysqlConfig !== null) {
            // Test connection & setup schema
            try {
                $dsn = "mysql:host={$mysqlConfig['host']};port={$mysqlConfig['port']};dbname={$mysqlConfig['database']};charset=utf8mb4";
                $testPdo = new PDO($dsn, $mysqlConfig['username'], $mysqlConfig['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 3
                ]);
                $this->setupMySQLSchema($testPdo);

                // Perform Migration JSON -> MySQL
                $this->migrateJsonToMySQL($testPdo);

                $this->pdo = $testPdo;
                $this->mode = 'mysql';
                return ['success' => true, 'message' => 'Successfully switched to MySQL and migrated data.'];
            } catch (PDOException $e) {
                // Revert settings to json
                $settings['storage_mode'] = 'json';
                $this->saveJsonFile('settings.json', $settings);
                return ['success' => false, 'message' => 'MySQL connection failed: ' . $e->getMessage()];
            }
        } else {
            // Switch to JSON
            // Perform Migration MySQL -> JSON (if connection is available)
            if ($this->pdo) {
                $this->migrateMySQLToJSON();
            }
            $this->mode = 'json';
            return ['success' => true, 'message' => 'Successfully switched to JSON storage.'];
        }
    }

    private function migrateJsonToMySQL($pdo) {
        // Users
        $users = $this->loadJsonFile('users.json');
        if (!empty($users)) {
            $pdo->exec("TRUNCATE TABLE users");
            $stmt = $pdo->prepare("INSERT INTO users (id, username, password_hash, role, full_name, email, phone, notifications_freq, notify_email, notify_push, notify_tg) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($users as $u) {
                $stmt->execute([
                    $u['id'], $u['username'], $u['password_hash'], $u['role'],
                    $u['full_name'] ?? '', $u['email'] ?? '', $u['phone'] ?? '',
                    $u['notifications_freq'] ?? 'immediate',
                    isset($u['notify_email']) ? (int)$u['notify_email'] : 1,
                    isset($u['notify_push']) ? (int)$u['notify_push'] : 1,
                    isset($u['notify_tg']) ? (int)$u['notify_tg'] : 0
                ]);
            }
        }

        // Tickets
        $tickets = $this->loadJsonFile('tickets.json');
        if (!empty($tickets)) {
            $pdo->exec("TRUNCATE TABLE tickets");
            $stmt = $pdo->prepare("INSERT INTO tickets (id, title, description, category, priority, status, created_by, assignee_id, created_at, updated_at, sla_deadline, custom_fields) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($tickets as $t) {
                $stmt->execute([
                    $t['id'], $t['title'], $t['description'] ?? '', $t['category'] ?? '',
                    $t['priority'] ?? 'medium', $t['status'] ?? 'new', $t['created_by'],
                    $t['assignee_id'] ?? null, $t['created_at'] ?? date('Y-m-d H:i:s'),
                    $t['updated_at'] ?? date('Y-m-d H:i:s'), $t['sla_deadline'] ?? null,
                    is_array($t['custom_fields'] ?? null) ? json_encode($t['custom_fields']) : ($t['custom_fields'] ?? '{}')
                ]);
            }
        }

        // Comments
        $comments = $this->loadJsonFile('comments.json');
        if (!empty($comments)) {
            $pdo->exec("TRUNCATE TABLE comments");
            $stmt = $pdo->prepare("INSERT INTO comments (id, ticket_id, user_id, comment_text, attachment_path, attachment_name, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($comments as $c) {
                $stmt->execute([
                    $c['id'], $c['ticket_id'], $c['user_id'], $c['comment_text'] ?? '',
                    $c['attachment_path'] ?? null, $c['attachment_name'] ?? null, $c['created_at'] ?? date('Y-m-d H:i:s')
                ]);
            }
        }

        // Settings
        $settings = $this->loadJsonFile('settings.json');
        if (!empty($settings)) {
            $pdo->exec("TRUNCATE TABLE settings");
            $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?)");
            foreach ($settings as $k => $v) {
                if ($k === 'mysql') continue; // don't store plain mysql connection in settings table database
                $stmt->execute([$k, is_array($v) ? json_encode($v) : $v]);
            }
        }

        // Logs
        $logs = $this->loadJsonFile('logs.json');
        if (!empty($logs)) {
            $pdo->exec("TRUNCATE TABLE logs");
            $stmt = $pdo->prepare("INSERT INTO logs (id, user_id, action, target_type, target_id, details, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($logs as $l) {
                $stmt->execute([
                    $l['id'], $l['user_id'] ?? 'system', $l['action'] ?? '',
                    $l['target_type'] ?? '', $l['target_id'] ?? '',
                    $l['details'] ?? '', $l['created_at'] ?? date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    private function migrateMySQLToJSON() {
        // Users
        $users = $this->pdo->query("SELECT * FROM users")->fetchAll();
        $this->saveJsonFile('users.json', $users);

        // Tickets
        $tickets = $this->pdo->query("SELECT * FROM tickets")->fetchAll();
        foreach ($tickets as &$t) {
            $t['custom_fields'] = json_decode($t['custom_fields'] ?? '{}', true);
        }
        $this->saveJsonFile('tickets.json', $tickets);

        // Comments
        $comments = $this->pdo->query("SELECT * FROM comments")->fetchAll();
        $this->saveJsonFile('comments.json', $comments);

        // Settings
        $dbSettings = $this->pdo->query("SELECT * FROM settings")->fetchAll();
        $settings = $this->loadJsonFile('settings.json'); // keep mysql config
        foreach ($dbSettings as $s) {
            $val = json_decode($s['value'], true);
            $settings[$s['key']] = ($val !== null) ? $val : $s['value'];
        }
        $this->saveJsonFile('settings.json', $settings);

        // Logs
        $logs = $this->pdo->query("SELECT * FROM logs")->fetchAll();
        $this->saveJsonFile('logs.json', $logs);
    }

    // --- Users API ---
    public function getUsers() {
        if ($this->mode === 'mysql') {
            return $this->pdo->query("SELECT * FROM users")->fetchAll();
        } else {
            return $this->loadJsonFile('users.json');
        }
    }

    public function getUserById($id) {
        if ($this->mode === 'mysql') {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        } else {
            $users = $this->loadJsonFile('users.json');
            foreach ($users as $u) {
                if ((string)$u['id'] === (string)$id) return $u;
            }
            return null;
        }
    }

    public function getUserByUsername($username) {
        if ($this->mode === 'mysql') {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetch() ?: null;
        } else {
            $users = $this->loadJsonFile('users.json');
            foreach ($users as $u) {
                if (strtolower($u['username']) === strtolower($username)) return $u;
            }
            return null;
        }
    }

    public function saveUser($user) {
        if ($this->mode === 'mysql') {
            if (empty($user['id'])) {
                $stmt = $this->pdo->prepare("INSERT INTO users (username, password_hash, role, full_name, email, phone, notifications_freq, notify_email, notify_push, notify_tg) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $user['username'], $user['password_hash'], $user['role'],
                    $user['full_name'] ?? '', $user['email'] ?? '', $user['phone'] ?? '',
                    $user['notifications_freq'] ?? 'immediate',
                    isset($user['notify_email']) ? (int)$user['notify_email'] : 1,
                    isset($user['notify_push']) ? (int)$user['notify_push'] : 1,
                    isset($user['notify_tg']) ? (int)$user['notify_tg'] : 0
                ]);
                $user['id'] = $this->pdo->lastInsertId();
            } else {
                $stmt = $this->pdo->prepare("UPDATE users SET username = ?, password_hash = ?, role = ?, full_name = ?, email = ?, phone = ?, notifications_freq = ?, notify_email = ?, notify_push = ?, notify_tg = ? WHERE id = ?");
                $stmt->execute([
                    $user['username'], $user['password_hash'], $user['role'],
                    $user['full_name'] ?? '', $user['email'] ?? '', $user['phone'] ?? '',
                    $user['notifications_freq'] ?? 'immediate',
                    isset($user['notify_email']) ? (int)$user['notify_email'] : 1,
                    isset($user['notify_push']) ? (int)$user['notify_push'] : 1,
                    isset($user['notify_tg']) ? (int)$user['notify_tg'] : 0,
                    $user['id']
                ]);
            }
            return $user;
        } else {
            $users = $this->loadJsonFile('users.json');
            if (empty($user['id'])) {
                $maxId = 0;
                foreach ($users as $u) {
                    if ((int)$u['id'] > $maxId) $maxId = (int)$u['id'];
                }
                $user['id'] = $maxId + 1;
                $users[] = $user;
            } else {
                $found = false;
                foreach ($users as &$u) {
                    if ((string)$u['id'] === (string)$user['id']) {
                        $u = $user;
                        $found = true;
                        break;
                    }
                }
                if (!$found) $users[] = $user;
            }
            $this->saveJsonFile('users.json', $users);
            return $user;
        }
    }

    public function deleteUser($id) {
        if ($this->mode === 'mysql') {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$id]);
        } else {
            $users = $this->loadJsonFile('users.json');
            $newUsers = [];
            foreach ($users as $u) {
                if ((string)$u['id'] !== (string)$id) {
                    $newUsers[] = $u;
                }
            }
            return $this->saveJsonFile('users.json', $newUsers);
        }
    }

    // --- Tickets API ---
    public function getTickets() {
        if ($this->mode === 'mysql') {
            $tickets = $this->pdo->query("SELECT * FROM tickets ORDER BY id DESC")->fetchAll();
            foreach ($tickets as &$t) {
                $t['custom_fields'] = json_decode($t['custom_fields'] ?? '{}', true);
            }
            return $tickets;
        } else {
            return array_reverse($this->loadJsonFile('tickets.json'));
        }
    }

    public function getTicketById($id) {
        if ($this->mode === 'mysql') {
            $stmt = $this->pdo->prepare("SELECT * FROM tickets WHERE id = ?");
            $stmt->execute([$id]);
            $ticket = $stmt->fetch();
            if ($ticket) {
                $ticket['custom_fields'] = json_decode($ticket['custom_fields'] ?? '{}', true);
            }
            return $ticket ?: null;
        } else {
            $tickets = $this->loadJsonFile('tickets.json');
            foreach ($tickets as $t) {
                if ((string)$t['id'] === (string)$id) return $t;
            }
            return null;
        }
    }

    public function saveTicket($ticket) {
        if ($this->mode === 'mysql') {
            $customFieldsJson = is_array($ticket['custom_fields'] ?? null) ? json_encode($ticket['custom_fields']) : ($ticket['custom_fields'] ?? '{}');
            if (empty($ticket['id'])) {
                $stmt = $this->pdo->prepare("INSERT INTO tickets (title, description, category, priority, status, created_by, assignee_id, created_at, updated_at, sla_deadline, custom_fields) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $ticket['title'], $ticket['description'] ?? '', $ticket['category'] ?? '',
                    $ticket['priority'] ?? 'medium', $ticket['status'] ?? 'new', $ticket['created_by'],
                    $ticket['assignee_id'] ?? null,
                    $ticket['created_at'] ?? date('Y-m-d H:i:s'),
                    $ticket['updated_at'] ?? date('Y-m-d H:i:s'),
                    $ticket['sla_deadline'] ?? null,
                    $customFieldsJson
                ]);
                $ticket['id'] = $this->pdo->lastInsertId();
            } else {
                $stmt = $this->pdo->prepare("UPDATE tickets SET title = ?, description = ?, category = ?, priority = ?, status = ?, created_by = ?, assignee_id = ?, updated_at = ?, sla_deadline = ?, custom_fields = ? WHERE id = ?");
                $stmt->execute([
                    $ticket['title'], $ticket['description'] ?? '', $ticket['category'] ?? '',
                    $ticket['priority'] ?? 'medium', $ticket['status'] ?? 'new', $ticket['created_by'],
                    $ticket['assignee_id'] ?? null,
                    date('Y-m-d H:i:s'),
                    $ticket['sla_deadline'] ?? null,
                    $customFieldsJson,
                    $ticket['id']
                ]);
            }
            return $ticket;
        } else {
            $tickets = $this->loadJsonFile('tickets.json');
            if (empty($ticket['id'])) {
                $maxId = 0;
                foreach ($tickets as $t) {
                    if ((int)$t['id'] > $maxId) $maxId = (int)$t['id'];
                }
                $ticket['id'] = $maxId + 1;
                $ticket['created_at'] = $ticket['created_at'] ?? date('Y-m-d H:i:s');
                $ticket['updated_at'] = date('Y-m-d H:i:s');
                $tickets[] = $ticket;
            } else {
                $found = false;
                $ticket['updated_at'] = date('Y-m-d H:i:s');
                foreach ($tickets as &$t) {
                    if ((string)$t['id'] === (string)$ticket['id']) {
                        $ticket['created_at'] = $t['created_at'];
                        $t = $ticket;
                        $found = true;
                        break;
                    }
                }
                if (!$found) $tickets[] = $ticket;
            }
            $this->saveJsonFile('tickets.json', $tickets);
            return $ticket;
        }
    }

    public function deleteTicket($id) {
        if ($this->mode === 'mysql') {
            $stmt = $this->pdo->prepare("DELETE FROM tickets WHERE id = ?");
            return $stmt->execute([$id]);
        } else {
            $tickets = $this->loadJsonFile('tickets.json');
            $newTickets = [];
            foreach ($tickets as $t) {
                if ((string)$t['id'] !== (string)$id) {
                    $newTickets[] = $t;
                }
            }
            return $this->saveJsonFile('tickets.json', $newTickets);
        }
    }

    // --- Comments API ---
    public function getCommentsByTicketId($ticket_id) {
        if ($this->mode === 'mysql') {
            $stmt = $this->pdo->prepare("SELECT * FROM comments WHERE ticket_id = ? ORDER BY id ASC");
            $stmt->execute([$ticket_id]);
            return $stmt->fetchAll();
        } else {
            $comments = $this->loadJsonFile('comments.json');
            $filtered = [];
            foreach ($comments as $c) {
                if ((string)$c['ticket_id'] === (string)$ticket_id) {
                    $filtered[] = $c;
                }
            }
            return $filtered;
        }
    }

    public function addComment($comment) {
        if ($this->mode === 'mysql') {
            $stmt = $this->pdo->prepare("INSERT INTO comments (ticket_id, user_id, comment_text, attachment_path, attachment_name, created_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $comment['ticket_id'], $comment['user_id'], $comment['comment_text'] ?? '',
                $comment['attachment_path'] ?? null, $comment['attachment_name'] ?? null,
                $comment['created_at'] ?? date('Y-m-d H:i:s')
            ]);
            $comment['id'] = $this->pdo->lastInsertId();
            return $comment;
        } else {
            $comments = $this->loadJsonFile('comments.json');
            $maxId = 0;
            foreach ($comments as $c) {
                if ((int)$c['id'] > $maxId) $maxId = (int)$c['id'];
            }
            $comment['id'] = $maxId + 1;
            $comment['created_at'] = $comment['created_at'] ?? date('Y-m-d H:i:s');
            $comments[] = $comment;
            $this->saveJsonFile('comments.json', $comments);
            return $comment;
        }
    }

    // --- Settings & Metadata API ---
    public function getSetting($key, $default = null) {
        if ($this->mode === 'mysql') {
            $stmt = $this->pdo->prepare("SELECT `value` FROM settings WHERE `key` = ?");
            $stmt->execute([$key]);
            $res = $stmt->fetch();
            if ($res) {
                $val = json_decode($res['value'], true);
                return ($val !== null) ? $val : $res['value'];
            }
            return $default;
        } else {
            $settings = $this->loadJsonFile('settings.json');
            return isset($settings[$key]) ? $settings[$key] : $default;
        }
    }

    public function saveSetting($key, $value) {
        if ($this->mode === 'mysql') {
            $valJson = is_array($value) ? json_encode($value) : $value;
            $stmt = $this->pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
            return $stmt->execute([$key, $valJson, $valJson]);
        } else {
            $settings = $this->loadJsonFile('settings.json');
            $settings[$key] = $value;
            return $this->saveJsonFile('settings.json', $settings);
        }
    }

    // --- Audit Logging API ---
    public function addLog($user_id, $action, $target_type = '', $target_id = '', $details = '') {
        if ($this->mode === 'mysql' && $this->pdo) {
            try {
                $stmt = $this->pdo->prepare("INSERT INTO logs (user_id, action, target_type, target_id, details, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $user_id, $action, $target_type, $target_id, $details, date('Y-m-d H:i:s')
                ]);
            } catch (Exception $e) {
                // Fail silently or fallback to JSON
            }
        } else {
            $logs = $this->loadJsonFile('logs.json');
            $maxId = 0;
            foreach ($logs as $l) {
                if ((int)$l['id'] > $maxId) $maxId = (int)$l['id'];
            }
            $log = [
                'id' => $maxId + 1,
                'user_id' => $user_id,
                'action' => $action,
                'target_type' => $target_type,
                'target_id' => $target_id,
                'details' => $details,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $logs[] = $log;
            // keep logs limited to 500 items to avoid infinite size in json
            if (count($logs) > 500) {
                $logs = array_slice($logs, -500);
            }
            $this->saveJsonFile('logs.json', $logs);
        }
    }

    public function getLogs() {
        if ($this->mode === 'mysql') {
            return $this->pdo->query("SELECT * FROM logs ORDER BY id DESC LIMIT 200")->fetchAll();
        } else {
            return array_reverse($this->loadJsonFile('logs.json'));
        }
    }
}
