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

        if (!empty($data['package_id'])) {
            $package = $this->store->findOne('packages', $data['package_id']);
            if ($package) $totalPrice += $package['base_price'];
        }

        if (!empty($data['procedure_ids'])) {
            foreach ($data['procedure_ids'] as $pid) {
                $proc = $this->store->findOne('procedures', $pid);
                if ($proc) $totalPrice += $proc['price'];
            }
        }

        if (!empty($data['service_ids'])) {
            foreach ($data['service_ids'] as $sid) {
                $service = $this->store->findOne('extra_services', $sid);
                if ($service) $totalPrice += $service['price'];
            }
        }

        return $totalPrice;
    }

    public function createBooking($data) {
        if (empty($data['check_in']) || empty($data['check_out']) || empty($data['room_id']) || empty($data['phone'])) {
            return false;
        }

        // Handle Guest association
        $guestsData = [
            'name' => $data['client_name'] ?? 'N/A',
            'phone' => $data['phone'],
            'citizenship' => $data['citizenship'] ?? '',
            'address' => $data['address'] ?? ''
        ];

        $guests = $this->store->findAll('guests');
        $guestId = null;
        foreach ($guests as $g) {
            if ($g['phone'] === $data['phone']) {
                $guestId = $g['id'];
                // Update guest info if provided
                if (!empty($data['citizenship'])) $g['citizenship'] = $data['citizenship'];
                if (!empty($data['address'])) $g['address'] = $data['address'];
                $g['name'] = $data['client_name'];
                $this->store->save('guests', $g);
                break;
            }
        }

        if (!$guestId) {
            $guestId = $this->store->save('guests', $guestsData);
        }
        $data['guest_id'] = $guestId;

        $data['total_price'] = $this->calculatePrice($data);
        $data['status'] = $data['status'] ?? 'new';
        $data['created_at'] = date('Y-m-d H:i:s');

        $bookingId = $this->store->save('bookings', $data);

        if ($bookingId) {
            $this->updateCalendar($data['room_id'], $data['check_in'], $data['check_out'], $bookingId);
        }

        return $bookingId;
    }

    public function releaseCalendar($bookingId) {
        $calendar = $this->store->findAll('room_calendar');
        foreach ($calendar as $entry) {
            if (isset($entry['booking_id']) && $entry['booking_id'] == $bookingId) {
                $this->store->delete('room_calendar', $entry['id']);
            }
        }
    }

    public function cancelBooking($bookingId) {
        $booking = $this->store->findOne('bookings', $bookingId);
        if ($booking) {
            $booking['status'] = 'cancelled';
            $this->store->save('bookings', $booking);
            $this->releaseCalendar($bookingId);
            return true;
        }
        return false;
    }

    private function updateCalendar($roomId, $checkIn, $checkOut, $bookingId) {
        try {
            $start = new \DateTime($checkIn);
            $end = new \DateTime($checkOut);

            if ($start >= $end) return;

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
        } catch (\Exception $e) {
            // Log error or ignore
        }
    }
}
