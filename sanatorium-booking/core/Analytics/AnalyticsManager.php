<?php
namespace Sanatorium\Core\Analytics;

use Sanatorium\Core\Database\JsonStore;

class AnalyticsManager {
    private $store;

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function getStats() {
        $bookings = $this->store->findAll('bookings');
        $rooms = $this->store->findAll('rooms');
        $calendar = $this->store->findAll('room_calendar');

        $totalIncome = 0;
        $statusCounts = ['new' => 0, 'confirmed' => 0, 'cancelled' => 0];
        $roomPopularity = [];
        $procedurePopularity = [];
        $servicePopularity = [];

        foreach ($bookings as $b) {
            if (($b['status'] ?? '') !== 'cancelled') {
                $totalIncome += (float)($b['total_price'] ?? 0);
            }
            $status = $b['status'] ?? 'new';
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

            if (isset($b['room_id'])) {
                $roomPopularity[$b['room_id']] = ($roomPopularity[$b['room_id']] ?? 0) + 1;
            }

            if (!empty($b['procedure_ids'])) {
                foreach($b['procedure_ids'] as $pid) {
                    $procedurePopularity[$pid] = ($procedurePopularity[$pid] ?? 0) + 1;
                }
            }
            if (!empty($b['service_ids'])) {
                foreach($b['service_ids'] as $sid) {
                    $servicePopularity[$sid] = ($servicePopularity[$sid] ?? 0) + 1;
                }
            }
        }

        arsort($roomPopularity);
        arsort($procedurePopularity);
        arsort($servicePopularity);

        // Occupancy last 30 days
        $occupiedSlots = 0;
        $today = time();
        $totalRooms = count($rooms);
        for ($i = 0; $i < 30; $i++) {
            $date = date('Y-m-d', $today - ($i * 86400));
            foreach ($calendar as $entry) {
                if ($entry['date'] === $date && ($entry['status'] ?? 'free') !== 'free') {
                    $occupiedSlots++;
                }
            }
        }
        $totalSlots = $totalRooms * 30;
        $occupancyRate = $totalSlots > 0 ? round(($occupiedSlots / $totalSlots) * 100, 1) : 0;

        return [
            'totalIncome' => $totalIncome,
            'totalBookings' => count($bookings),
            'occupancyRate' => $occupancyRate,
            'statusCounts' => $statusCounts,
            'roomPopularity' => $roomPopularity,
            'procedurePopularity' => $procedurePopularity,
            'servicePopularity' => $servicePopularity
        ];
    }
}
