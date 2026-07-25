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
                tags TEXT,
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
            )",
            "CREATE TABLE IF NOT EXISTS subtasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                task_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                completed TINYINT(1) DEFAULT 0
            )",
            "CREATE TABLE IF NOT EXISTS work_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                task_id INT NOT NULL,
                user_id INT NOT NULL,
                hours DECIMAL(5,2) NOT NULL,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                task_id INT NOT NULL,
                user_id INT NOT NULL,
                action VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS feedback (
                id INT AUTO_INCREMENT PRIMARY KEY,
                task_id INT UNIQUE NOT NULL,
                rating INT NOT NULL,
                comment TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )"
        ];

        foreach ($queries as $sql) {
            $this->pdo->exec($sql);
        }

        // Add column 'tags' to existing tasks table if it does not exist
        try {
            $this->pdo->exec("ALTER TABLE tasks ADD COLUMN tags TEXT NULL");
        } catch (PDOException $e) {
            // Column already exists, ignore
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
        if ($this->mode === 'mysql' && $this->pdo) {
            return $this->pdo->query("SELECT * FROM users")->fetchAll();
        }
        return json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true) ?: [];
    }

    public function saveUsers($users) {
        if ($this->mode === 'mysql' && $this->pdo) {
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
        if ($this->mode === 'mysql' && $this->pdo) {
            $tasks = $this->pdo->query("SELECT * FROM tasks ORDER BY created_at DESC")->fetchAll();
            foreach ($tasks as &$t) {
                $t['attachments'] = json_decode($t['attachments'], true) ?: [];
                $t['custom_fields'] = json_decode($t['custom_fields'], true) ?: [];
                $t['tags'] = json_decode($t['tags'] ?? '[]', true) ?: [];

                // Fetch subtasks
                $stStmt = $this->pdo->prepare("SELECT * FROM subtasks WHERE task_id = ?");
                $stStmt->execute([$t['id']]);
                $t['subtasks'] = $stStmt->fetchAll();
                foreach ($t['subtasks'] as &$st) {
                    $st['completed'] = (bool)$st['completed'];
                }

                // Fetch work logs
                $wlStmt = $this->pdo->prepare("SELECT * FROM work_logs WHERE task_id = ? ORDER BY created_at DESC");
                $wlStmt->execute([$t['id']]);
                $t['work_logs'] = $wlStmt->fetchAll();

                // Fetch audit logs
                $alStmt = $this->pdo->prepare("SELECT * FROM audit_logs WHERE task_id = ? ORDER BY created_at DESC");
                $alStmt->execute([$t['id']]);
                $t['audit_logs'] = $alStmt->fetchAll();

                // Fetch feedback
                $fbStmt = $this->pdo->prepare("SELECT * FROM feedback WHERE task_id = ?");
                $fbStmt->execute([$t['id']]);
                $t['feedback'] = $fbStmt->fetch() ?: null;
            }
            return $tasks;
        }
        $file = __DIR__ . '/../data/tasks.json';
        $tasks = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
        foreach ($tasks as &$t) {
            if (!isset($t['tags'])) $t['tags'] = [];
            if (!isset($t['subtasks'])) $t['subtasks'] = [];
            if (!isset($t['work_logs'])) $t['work_logs'] = [];
            if (!isset($t['audit_logs'])) $t['audit_logs'] = [];
            if (!isset($t['feedback'])) $t['feedback'] = null;
        }
        return $tasks;
    }

    public function saveTasks($tasks) {
        if ($this->mode === 'mysql' && $this->pdo) {
            foreach ($tasks as $t) {
                $stmt = $this->pdo->prepare("INSERT INTO tasks (id, title, description, category, priority, status, creator_id, creator_department, assigned_to, completed_at, attachments, custom_fields, tags, created_at, deadline)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), category=VALUES(category), priority=VALUES(priority), status=VALUES(status), assigned_to=VALUES(assigned_to), completed_at=VALUES(completed_at), attachments=VALUES(attachments), custom_fields=VALUES(custom_fields), tags=VALUES(tags), deadline=VALUES(deadline)");
                $stmt->execute([
                    $t['id'], $t['title'], $t['description'], $t['category'], $t['priority'], $t['status'],
                    $t['creator_id'], $t['creator_department'], $t['assigned_to'] ?? null, $t['completed_at'] ?? null,
                    json_encode($t['attachments'] ?? []), json_encode($t['custom_fields'] ?? []),
                    json_encode($t['tags'] ?? []),
                    $t['created_at'], $t['deadline'] ?? null
                ]);

                // Sync subtasks
                if (isset($t['subtasks'])) {
                    $this->pdo->prepare("DELETE FROM subtasks WHERE task_id = ?")->execute([$t['id']]);
                    foreach ($t['subtasks'] as $st) {
                        $insSt = $this->pdo->prepare("INSERT INTO subtasks (id, task_id, title, completed) VALUES (?, ?, ?, ?)");
                        $insSt->execute([$st['id'] ?? null, $t['id'], $st['title'], $st['completed'] ? 1 : 0]);
                    }
                }

                // Sync work logs
                if (isset($t['work_logs'])) {
                    $this->pdo->prepare("DELETE FROM work_logs WHERE task_id = ?")->execute([$t['id']]);
                    foreach ($t['work_logs'] as $wl) {
                        $insWl = $this->pdo->prepare("INSERT INTO work_logs (id, task_id, user_id, hours, notes, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                        $insWl->execute([$wl['id'] ?? null, $t['id'], $wl['user_id'], $wl['hours'], $wl['notes'], $wl['created_at']]);
                    }
                }

                // Sync audit logs
                if (isset($t['audit_logs'])) {
                    $this->pdo->prepare("DELETE FROM audit_logs WHERE task_id = ?")->execute([$t['id']]);
                    foreach ($t['audit_logs'] as $al) {
                        $insAl = $this->pdo->prepare("INSERT INTO audit_logs (id, task_id, user_id, action, created_at) VALUES (?, ?, ?, ?, ?)");
                        $insAl->execute([$al['id'] ?? null, $t['id'], $al['user_id'], $al['action'], $al['created_at']]);
                    }
                }

                // Sync feedback
                if (isset($t['feedback']) && !empty($t['feedback'])) {
                    $fb = $t['feedback'];
                    $stmtFb = $this->pdo->prepare("INSERT INTO feedback (task_id, rating, comment, created_at) VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment)");
                    $stmtFb->execute([$t['id'], $fb['rating'], $fb['comment'], $fb['created_at']]);
                }
            }
        } else {
            file_put_contents(__DIR__ . '/../data/tasks.json', json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    public function addAuditLog($taskId, $userId, $action) {
        $log = [
            'id' => time() . rand(100, 999),
            'task_id' => $taskId,
            'user_id' => $userId,
            'action' => $action,
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($this->mode === 'mysql' && $this->pdo) {
            $stmt = $this->pdo->prepare("INSERT INTO audit_logs (task_id, user_id, action, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$taskId, $userId, $action, $log['created_at']]);
        } else {
            $tasks = $this->getTasks();
            foreach ($tasks as &$t) {
                if ($t['id'] == $taskId) {
                    if (!isset($t['audit_logs'])) $t['audit_logs'] = [];
                    $t['audit_logs'][] = $log;
                    break;
                }
            }
            $this->saveTasks($tasks);
        }
    }

    public function addSubtask($taskId, $title) {
        $st = [
            'id' => time() . rand(100, 999),
            'task_id' => $taskId,
            'title' => $title,
            'completed' => false
        ];

        if ($this->mode === 'mysql' && $this->pdo) {
            $stmt = $this->pdo->prepare("INSERT INTO subtasks (task_id, title, completed) VALUES (?, ?, 0)");
            $stmt->execute([$taskId, $title]);
            $st['id'] = $this->pdo->lastInsertId();
        } else {
            $tasks = $this->getTasks();
            foreach ($tasks as &$t) {
                if ($t['id'] == $taskId) {
                    if (!isset($t['subtasks'])) $t['subtasks'] = [];
                    $t['subtasks'][] = $st;
                    break;
                }
            }
            $this->saveTasks($tasks);
        }
        return $st;
    }

    public function toggleSubtask($taskId, $subtaskId, $completed) {
        if ($this->mode === 'mysql' && $this->pdo) {
            $stmt = $this->pdo->prepare("UPDATE subtasks SET completed = ? WHERE id = ?");
            $stmt->execute([$completed ? 1 : 0, $subtaskId]);
        } else {
            $tasks = $this->getTasks();
            foreach ($tasks as &$t) {
                if ($t['id'] == $taskId) {
                    foreach ($t['subtasks'] as &$st) {
                        if ($st['id'] == $subtaskId) {
                            $st['completed'] = (bool)$completed;
                            break;
                        }
                    }
                }
            }
            $this->saveTasks($tasks);
        }
    }

    public function addWorkLog($taskId, $userId, $hours, $notes) {
        $wl = [
            'id' => time() . rand(100, 999),
            'task_id' => $taskId,
            'user_id' => $userId,
            'hours' => floatval($hours),
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($this->mode === 'mysql' && $this->pdo) {
            $stmt = $this->pdo->prepare("INSERT INTO work_logs (task_id, user_id, hours, notes, created_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$taskId, $userId, $hours, $notes, $wl['created_at']]);
            $wl['id'] = $this->pdo->lastInsertId();
        } else {
            $tasks = $this->getTasks();
            foreach ($tasks as &$t) {
                if ($t['id'] == $taskId) {
                    if (!isset($t['work_logs'])) $t['work_logs'] = [];
                    $t['work_logs'][] = $wl;
                    break;
                }
            }
            $this->saveTasks($tasks);
        }
        return $wl;
    }

    public function addFeedback($taskId, $rating, $comment) {
        $fb = [
            'task_id' => $taskId,
            'rating' => intval($rating),
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($this->mode === 'mysql' && $this->pdo) {
            $stmt = $this->pdo->prepare("INSERT INTO feedback (task_id, rating, comment, created_at) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment)");
            $stmt->execute([$taskId, $rating, $comment, $fb['created_at']]);
        } else {
            $tasks = $this->getTasks();
            foreach ($tasks as &$t) {
                if ($t['id'] == $taskId) {
                    $t['feedback'] = $fb;
                    break;
                }
            }
            $this->saveTasks($tasks);
        }
        return $fb;
    }

    public function getComments() {
        if ($this->mode === 'mysql' && $this->pdo) {
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
        if ($this->mode === 'mysql' && $this->pdo) {
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
        if ($this->mode === 'mysql' && $this->pdo) {
            return $this->pdo->query("SELECT * FROM recovery_tokens")->fetchAll();
        }
        $file = __DIR__ . '/../data/recovery_tokens.json';
        return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    }

    public function saveRecoveryTokens($tokens) {
        if ($this->mode === 'mysql' && $this->pdo) {
            $this->pdo->exec("DELETE FROM recovery_tokens");
            $ins = $this->pdo->prepare("INSERT INTO recovery_tokens (id, token, user_id, expires_at) VALUES (?, ?, ?, ?)");
            foreach ($tokens as $t) {
                $ins->execute([$t['id'], $t['token'], $t['user_id'], $t['expires_at']]);
            }
        } else {
            file_put_contents(__DIR__ . '/../data/recovery_tokens.json', json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}
