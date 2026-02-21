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

    public function getStats(?string $startDate = null, ?string $endDate = null): array {
        $allRequests = $this->requestStore->read();
        $requests = [];

        $startTs = $startDate ? strtotime($startDate . ' 00:00:00') : null;
        $endTs = $endDate ? strtotime($endDate . ' 23:59:59') : null;

        foreach ($allRequests as $req) {
            $createdAt = strtotime($req['created_at']);
            if ($startTs && $createdAt < $startTs) continue;
            if ($endTs && $createdAt > $endTs) continue;
            $requests[] = $req;
        }

        $stats = [
            'total' => count($requests),
            'by_status' => [],
            'by_service' => [],
            'by_performer' => [],
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

            $perfId = $req['performer_id'] ?? null;
            if ($perfId) {
                if (!isset($stats['by_performer'][$perfId])) {
                    $stats['by_performer'][$perfId] = ['total' => 0, 'completed' => 0, 'total_hours' => 0, 'ratings' => [], 'avg_rating' => 0];
                }
                $stats['by_performer'][$perfId]['total']++;
            }

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
                    if (isset($h['status']) && $h['status'] === 'completed') {
                        $completedAt = strtotime($h['timestamp']);
                        break;
                    }
                }
                if ($completedAt) {
                    $duration = ($completedAt - strtotime($req['created_at'])) / 3600;
                    $totalHours += $duration;
                    if ($perfId) {
                        $stats['by_performer'][$perfId]['completed']++;
                        $stats['by_performer'][$perfId]['total_hours'] += $duration;
                        if (isset($req['rating']) && $req['rating'] > 0) {
                            $stats['by_performer'][$perfId]['ratings'][] = (int)$req['rating'];
                        }
                    }
                }
            }
        }

        if ($stats['completed_count'] > 0) {
            $stats['avg_hours'] = round($totalHours / $stats['completed_count'], 1);
        }

        // Calculate average ratings for performers
        foreach ($stats['by_performer'] as $pid => &$pdata) {
            if (count($pdata['ratings']) > 0) {
                $pdata['avg_rating'] = round(array_sum($pdata['ratings']) / count($pdata['ratings']), 1);
            }
        }

        return $stats;
    }

    public function getBestPerformers(int $limit = 5): array {
        $stats = $this->getStats();
        $rankings = [];

        $userStore = new JsonStore('data/users.json');
        $users = $userStore->read();
        $userNames = [];
        foreach ($users as $u) $userNames[$u['id']] = $u['name'];

        foreach ($stats['by_performer'] as $id => $data) {
            if ($data['completed'] === 0) continue;

            // Formula: Rating * log10(completed + 1) to balance quality and quantity
            $score = $data['avg_rating'] * log10($data['completed'] + 1);

            $rankings[] = [
                'id' => $id,
                'name' => $userNames[$id] ?? "Сотрудник #$id",
                'completed' => $data['completed'],
                'avg_rating' => $data['avg_rating'],
                'score' => round($score, 2)
            ];
        }

        usort($rankings, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($rankings, 0, $limit);
    }
}
