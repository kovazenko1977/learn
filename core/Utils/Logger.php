<?php

declare(strict_types=1);

namespace App\Utils;

use App\Storage\StorageManager;
use App\Auth\AuthManager;

class Logger
{
    private StorageManager $storage;
    private ?AuthManager $auth;

    public function __construct(StorageManager $storage, ?AuthManager $auth = null)
    {
        $this->storage = $storage;
        $this->auth = $auth;
    }

    public function log(string $action, string $module, array $details = []): void
    {
        $user = $this->auth ? $this->auth->getCurrentUser() : null;

        $this->storage->insert('audit_logs', [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $user['id'] ?? 'system',
            'username' => $user['username'] ?? 'system',
            'action' => $action,
            'module' => $module,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    }
}
