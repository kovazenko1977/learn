<?php
namespace Sanatorium\Core\Booking;

use Sanatorium\Core\Database\JsonStore;

class BookingManager {
    private $store;

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function calculatePrice($data) {
        $checkIn = strtotime($data['check_in']);
        $checkOut = strtotime($data['check_out']);
        $days = max(1, ($checkOut - $checkIn) / (60 * 60 * 24));

        $room = $this->store->findOne('rooms', $data['room_id']);
        if (!$room) return 0;

        $totalPrice = $room['price_per_day'] * $days;

        // Add packages
        if (!empty($data['package_id'])) {
            $package = $this->store->findOne('packages', $data['package_id']);
            if ($package) {
                $totalPrice += $package['base_price'];
            }
        }

        // Add procedures
        if (!empty($data['procedure_ids'])) {
            foreach ($data['procedure_ids'] as $pid) {
                $proc = $this->store->findOne('procedures', $pid);
                if ($proc) {
                    $totalPrice += $proc['price'];
                }
            }
        }

        // Add services
        if (!empty($data['service_ids'])) {
            foreach ($data['service_ids'] as $sid) {
                $service = $this->store->findOne('extra_services', $sid);
                if ($service) {
                    $totalPrice += $service['price'];
                }
            }
        }

        return $totalPrice;
    }

    public function createBooking($data) {
        if (empty($data['check_in']) || empty($data['check_out']) || empty($data['room_id']) || empty($data['phone'])) {
            return false;
        }

        $data['total_price'] = $this->calculatePrice($data);
        $data['status'] = 'new';
        $data['created_at'] = date('Y-m-d H:i:s');

        $bookingId = $this->store->save('bookings', $data);

        if ($bookingId) {
            $this->updateCalendar($data['room_id'], $data['check_in'], $data['check_out'], $bookingId);

            $logger = new \Sanatorium\Core\Helpers\Logger(__DIR__ . '/../../logs');
            $logger->log("New booking created: ID $bookingId, Room {$data['room_id']}, Total {$data['total_price']}");
        }

        return $bookingId;
    }

    private function updateCalendar($roomId, $checkIn, $checkOut, $bookingId) {
        $start = new \DateTime($checkIn);
        $end = new \DateTime($checkOut);
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end);

        foreach ($period as $date) {
            $this->store->save('room_calendar', [
                'room_id' => $roomId,
                'date' => $date->format('Y-m-d'),
                'status' => 'booked',
                'booking_id' => $bookingId
            ]);
        }
    }
}
