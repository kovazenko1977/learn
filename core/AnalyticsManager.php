<?php
namespace Hop\Core;

class AnalyticsManager {
    private JsonStore $requestStore;
    private JsonStore $serviceStore;
    private ?array $settings = null;

    public function __construct(JsonStore $requestStore, JsonStore $serviceStore, ?array $settings = null) {
        $this->requestStore = $requestStore;
        $this->serviceStore = $serviceStore;
        $this->settings = $settings;
    }

    public function getStats(): array {
        $requests = $this->requestStore->read();

        $stats = [
            'total' => count($requests),
            'by_status' => [],
            'by_service' => [],
            'overdue' => 0,
            'avg_hours' => 0,
            'completed_count' => 0
        ];

        $slaConfig = $this->settings['sla'] ?? [];
        $totalHours = 0;

        foreach ($requests as $req) {
            $status = $req['status'];
            $stats['by_status'][$status] = ($stats['by_status'][$status] ?? 0) + 1;

            $serviceId = $req['service_id'];
            $stats['by_service'][$serviceId] = ($stats['by_service'][$serviceId] ?? 0) + 1;

            // Overdue check
            if (!in_array($status, ['completed', 'closed'])) {
                $hoursLimit = $slaConfig[$req['priority']] ?? 24;
                $createdTime = strtotime($req['created_at']);
                if (time() > ($createdTime + ($hoursLimit * 3600))) {
                    $stats['overdue']++;
                }
            }

            // Performance: find completion time from history
            if ($status === 'completed' || $status === 'closed') {
                $stats['completed_count']++;
                $completedAt = null;
                foreach ($req['history'] as $h) {
                    if ($h['to'] === 'completed') {
                        $completedAt = strtotime($h['timestamp']);
                        break;
                    }
                }
                if ($completedAt) {
                    $totalHours += ($completedAt - strtotime($req['created_at'])) / 3600;
                }
            }
        }

        if ($stats['completed_count'] > 0) {
            $stats['avg_hours'] = round($totalHours / $stats['completed_count'], 1);
        }

        return $stats;
    }
}
