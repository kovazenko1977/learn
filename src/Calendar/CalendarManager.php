<?php
namespace App\Calendar;

use App\Database\JsonStore;

class CalendarManager {
    private $store;

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function getOccupancyData($startDate, $endDate) {
        $bookings = $this->store->findAll('bookings');
        $occupancy = [];

        if (is_array($bookings)) {
            foreach ($bookings as $b) {
                if (!is_array($b) || ($b['status'] ?? '') === 'cancelled') continue;

                $start = strtotime(substr($b['check_in'], 0, 10));
                $end = strtotime(substr($b['check_out'], 0, 10));

                $current = $start;
                while ($current < $end) {
                    $dateStr = date('Y-m-d', $current);
                    if ($dateStr >= $startDate && $dateStr <= $endDate) {
                        $occupancy[$b['room_id']][$dateStr] = [
                            'status' => $b['status'] ?? 'booked',
                            'booking_id' => $b['id']
                        ];
                    }
                    $current = strtotime("+1 day", $current);
                }
            }
        }
        return $occupancy;
    }

    public function getDateRange($startDate, $endDate) {
        try {
            $period = new \DatePeriod(
                new \DateTime($startDate),
                new \DateInterval('P1D'),
                (new \DateTime($endDate))->modify('+1 day')
            );
            $dates = [];
            foreach ($period as $date) {
                $dates[] = $date->format('Y-m-d');
            }
            return $dates;
        } catch (\Exception $e) {
            return [];
        }
    }
}
