<?php
namespace Sanatorium\Core\Calendar;

use Sanatorium\Core\Database\JsonStore;

class CalendarManager {
    private $store;

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function getOccupancyData($startDate, $endDate) {
        $calendar = $this->store->findAll('room_calendar');
        $occupancy = [];
        foreach ($calendar as $entry) {
            if ($entry['date'] >= $startDate && $entry['date'] <= $endDate) {
                $occupancy[$entry['room_id']][$entry['date']] = [
                    'status' => $entry['status'] ?? 'booked',
                    'booking_id' => $entry['booking_id'] ?? null
                ];
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
