<?php

class Storage {
    private $mode;
    private $config;
    private $pdo = null;

    public function __construct($settings) {
        $this->mode = $settings['storage_mode'] ?? 'json';
        $this->config = $settings['mysql_config'] ?? [];

        if ($this->mode === 'mysql') {
            $this->connect();
            $this->ensureSchema();
        }
    }

    private function connect() {
        $dsn = "mysql:host={$this->config['host']};dbname={$this->config['db']};charset=utf8mb4";
        try {
            $this->pdo = new PDO($dsn, $this->config['user'], $this->config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            error_log("MySQL Connection failed: " . $e->getMessage());
            $this->mode = 'json';
        }
    }

    private function ensureSchema() {
        if (!$this->pdo) return;

        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(255),
                role VARCHAR(50),
                department VARCHAR(100)
            )",
            "CREATE TABLE IF NOT EXISTS tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(100),
                priority VARCHAR(50),
                status VARCHAR(50) DEFAULT 'new',
                creator_id INT,
                creator_department VARCHAR(100),
                assigned_to INT NULL,
                completed_at DATETIME NULL,
                attachments TEXT,
                custom_fields TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deadline DATETIME NULL
            )",
            "CREATE TABLE IF NOT EXISTS comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                task_id INT,
                user_id INT,
                content TEXT,
                attachments TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS recovery_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                token VARCHAR(255) UNIQUE,
                user_id INT,
                expires_at DATETIME
            )"
        ];

        foreach ($queries as $sql) {
            $this->pdo->exec($sql);
        }

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        if ($stmt->fetchColumn() == 0) {
            $users = json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true);
            $ins = $this->pdo->prepare("INSERT INTO users (id, username, password, full_name, role, department) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($users as $u) {
                $ins->execute([$u['id'], $u['username'], $u['password'], $u['full_name'], $u['role'], $u['department']]);
            }
        }
    }

    public function getUsers() {
        if ($this->mode === 'mysql') {
            return $this->pdo->query("SELECT * FROM users")->fetchAll();
        }
        return json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true) ?: [];
    }

    public function saveUsers($users) {
        if ($this->mode === 'mysql') {
            foreach ($users as $u) {
                $stmt = $this->pdo->prepare("INSERT INTO users (id, username, password, full_name, role, department)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE username=VALUES(username), password=VALUES(password), full_name=VALUES(full_name), role=VALUES(role), department=VALUES(department)");
                $stmt->execute([$u['id'], $u['username'], $u['password'], $u['full_name'], $u['role'], $u['department']]);
            }
        } else {
            file_put_contents(__DIR__ . '/../data/users.json', json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    public function getTasks() {
        if ($this->mode === 'mysql') {
            $tasks = $this->pdo->query("SELECT * FROM tasks ORDER BY created_at DESC")->fetchAll();
            foreach ($tasks as &$t) {
                $t['attachments'] = json_decode($t['attachments'], true) ?: [];
                $t['custom_fields'] = json_decode($t['custom_fields'], true) ?: [];
            }
            return $tasks;
        }
        $file = __DIR__ . '/../data/tasks.json';
        return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    }

    public function saveTasks($tasks) {
        if ($this->mode === 'mysql') {
            foreach ($tasks as $t) {
                $stmt = $this->pdo->prepare("INSERT INTO tasks (id, title, description, category, priority, status, creator_id, creator_department, assigned_to, completed_at, attachments, custom_fields, created_at, deadline)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), category=VALUES(category), priority=VALUES(priority), status=VALUES(status), assigned_to=VALUES(assigned_to), completed_at=VALUES(completed_at), attachments=VALUES(attachments), custom_fields=VALUES(custom_fields), deadline=VALUES(deadline)");
                $stmt->execute([
                    $t['id'], $t['title'], $t['description'], $t['category'], $t['priority'], $t['status'],
                    $t['creator_id'], $t['creator_department'], $t['assigned_to'] ?? null, $t['completed_at'] ?? null,
                    json_encode($t['attachments'] ?? []), json_encode($t['custom_fields'] ?? []),
                    $t['created_at'], $t['deadline'] ?? null
                ]);
            }
        } else {
            file_put_contents(__DIR__ . '/../data/tasks.json', json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    public function getComments() {
        if ($this->mode === 'mysql') {
            $comments = $this->pdo->query("SELECT * FROM comments ORDER BY created_at ASC")->fetchAll();
            foreach ($comments as &$c) {
                $c['attachments'] = json_decode($c['attachments'], true) ?: [];
            }
            return $comments;
        }
        $file = __DIR__ . '/../data/comments.json';
        return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    }

    public function saveComments($comments) {
        if ($this->mode === 'mysql') {
            foreach ($comments as $c) {
                $stmt = $this->pdo->prepare("INSERT INTO comments (id, task_id, user_id, content, attachments, created_at)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE content=VALUES(content), attachments=VALUES(attachments)");
                $stmt->execute([
                    $c['id'], $c['task_id'], $c['user_id'], $c['content'],
                    json_encode($c['attachments'] ?? []), $c['created_at']
                ]);
            }
        } else {
            file_put_contents(__DIR__ . '/../data/comments.json', json_encode($comments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    public function getRecoveryTokens() {
        if ($this->mode === 'mysql') {
            return $this->pdo->query("SELECT * FROM recovery_tokens")->fetchAll();
        }
        $file = __DIR__ . '/../data/recovery_tokens.json';
        return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    }

    public function saveRecoveryTokens($tokens) {
        if ($this->mode === 'mysql') {
            $this->pdo->exec("DELETE FROM recovery_tokens"); // Tokens are few and transient, but let's be careful
            $ins = $this->pdo->prepare("INSERT INTO recovery_tokens (id, token, user_id, expires_at) VALUES (?, ?, ?, ?)");
            foreach ($tokens as $t) {
                $ins->execute([$t['id'], $t['token'], $t['user_id'], $t['expires_at']]);
            }
        } else {
            file_put_contents(__DIR__ . '/../data/recovery_tokens.json', json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}
