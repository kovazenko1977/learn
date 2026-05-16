<?php
namespace App\Analytics;

use App\Database\JsonStore;

class AnalyticsManager {
    private $store;

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function getStats() {
        $bookings = $this->store->findAll('bookings');
        $rooms = $this->store->findAll('rooms');

        $totalIncome = 0;
        $statusCounts = ['new' => 0, 'confirmed' => 0, 'cancelled' => 0];
        $roomPopularity = [];
        $procedurePopularity = [];
        $servicePopularity = [];

        if (is_array($bookings)) {
            foreach ($bookings as $b) {
                if (!is_array($b)) continue;
                if (($b['status'] ?? '') !== 'cancelled') {
                    $totalIncome += (float)($b['total_price'] ?? 0);
                }
                $status = $b['status'] ?? 'new';
                $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

                if (isset($b['room_id'])) {
                    $roomPopularity[$b['room_id']] = ($roomPopularity[$b['room_id']] ?? 0) + 1;
                }

                if (!empty($b['procedure_ids']) && is_array($b['procedure_ids'])) {
                    foreach($b['procedure_ids'] as $pid) {
                        $procedurePopularity[$pid] = ($procedurePopularity[$pid] ?? 0) + 1;
                    }
                }
                if (!empty($b['service_ids']) && is_array($b['service_ids'])) {
                    foreach($b['service_ids'] as $sid) {
                        $servicePopularity[$sid] = ($servicePopularity[$sid] ?? 0) + 1;
                    }
                }
            }
        }

        arsort($roomPopularity);
        arsort($procedurePopularity);
        arsort($servicePopularity);

        // Occupancy last 30 days based on bookings
        $occupiedSlots = 0;
        $today = time();
        $totalRooms = count($rooms);

        $last30Days = [];
        for ($i = 0; $i < 30; $i++) {
            $last30Days[] = date('Y-m-d', $today - ($i * 86400));
        }

        if (is_array($bookings)) {
            foreach ($bookings as $b) {
                if (!is_array($b) || ($b['status'] ?? '') === 'cancelled') continue;

                $bStart = strtotime(substr($b['check_in'], 0, 10));
                $bEnd = strtotime(substr($b['check_out'], 0, 10));

                foreach ($last30Days as $dayStr) {
                    $dayTime = strtotime($dayStr);
                    if ($dayTime >= $bStart && $dayTime < $bEnd) {
                        $occupiedSlots++;
                    }
                }
            }
        }

        $totalSlots = $totalRooms * 30;
        $occupancyRate = $totalSlots > 0 ? round(($occupiedSlots / $totalSlots) * 100, 1) : 0;

        return [
            'totalIncome' => $totalIncome,
            'totalBookings' => count($bookings),
            'occupancyRate' => min(100, $occupancyRate),
            'statusCounts' => $statusCounts,
            'roomPopularity' => $roomPopularity,
            'procedurePopularity' => $procedurePopularity,
            'servicePopularity' => $servicePopularity
        ];
    }

    public function getBasicStats() {
        return $this->getStats();
    }
}
