<?php
namespace Sanatorium\Core\Helpers;

use Sanatorium\Core\Database\JsonStore;

class AuditLogger {
    private JsonStore $store;

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function log(string $action, string $targetType, $targetId, string $details, string $user = 'admin'): void {
        $log = [
            'id' => time() . '_' . rand(100, 999),
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details,
            'user' => $user
        ];

        try {
            $this->store->insert('audit_logs', $log);
        } catch (\Exception $e) {
            // Silently fail if logging fails to prevent breaking main flow
        }
    }

    public function getLogs(int $limit = 100): array {
        $logs = $this->store->findAll('audit_logs');
        usort($logs, fn($a, $b) => strcmp($b['timestamp'], $a['timestamp']));
        return array_slice($logs, 0, $limit);
    }
}
