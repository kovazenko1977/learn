<?php
namespace Sanatorium\Core\Booking;

use Sanatorium\Core\Database\JsonStore;

class BookingManager {
    private $store;

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function calculatePrice($data) {
        if (!is_array($data)) return 0;

        $checkInStr = $data['check_in'] ?? '';
        $checkOutStr = $data['check_out'] ?? '';

        $checkIn = strtotime($checkInStr);
        $checkOut = strtotime($checkOutStr);
        if (!$checkIn || !$checkOut) return 0;

        $room = $this->store->findOne('rooms', $data['room_id'] ?? 0);
        if (!$room || !is_array($room)) return 0;

        $roomClass = $this->store->findOne('room_classes', $room['room_class_id'] ?? 0);
        $isHourly = ($roomClass && stripos($roomClass['name'], 'Сауна') !== false) || !empty($data['is_hourly']);

        if ($isHourly) {
            $hours = ceil(max(3600, ($checkOut - $checkIn)) / 3600);
            $totalPrice = (float)($room['price_per_hour'] ?? ($room['price_per_day'] / 24)) * $hours;
        } else {
            $days = ceil(max(86400, ($checkOut - $checkIn)) / 86400);
            $totalPrice = (float)($room['price_per_day'] ?? 0) * $days;
        }

        if (!empty($data['package_id'])) {
            $package = $this->store->findOne('packages', $data['package_id']);
            if ($package) $totalPrice += $package['base_price'];
        }

        if (!empty($data['procedure_ids']) && is_array($data['procedure_ids'])) {
            foreach ($data['procedure_ids'] as $pid) {
                $proc = $this->store->findOne('procedures', $pid);
                if ($proc && is_array($proc)) $totalPrice += ($proc['price'] ?? 0);
            }
        }

        if (!empty($data['service_ids']) && is_array($data['service_ids'])) {
            foreach ($data['service_ids'] as $sid) {
                $service = $this->store->findOne('extra_services', $sid);
                if ($service && is_array($service)) $totalPrice += ($service['price'] ?? 0);
            }
        }

        return $totalPrice;
    }

    public function isAvailable($roomId, $checkIn, $checkOut, $excludeBookingId = null) {
        $start = strtotime($checkIn);
        $end = strtotime($checkOut);

        $bookings = $this->store->findAll('bookings');
        if (is_array($bookings)) {
            foreach ($bookings as $b) {
                if (!is_array($b) || ($b['status'] ?? '') === 'cancelled') continue;
                if ($excludeBookingId && ($b['id'] ?? '') == $excludeBookingId) continue;

                if (($b['room_id'] ?? 0) == $roomId) {
                    $bStart = strtotime($b['check_in']);
                    $bEnd = strtotime($b['check_out']);

                    if ($start < $bEnd && $end > $bStart) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function createBooking($data) {
        if (empty($data['check_in']) || empty($data['check_out']) || empty($data['room_id']) || empty($data['phone'])) {
            return false;
        }

        if (!$this->isAvailable($data['room_id'], $data['check_in'], $data['check_out'])) {
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
        if (is_array($guests)) {
            foreach ($guests as $g) {
                if (!is_array($g)) continue;
                if (($g['phone'] ?? '') === $data['phone']) {
                    $guestId = $g['id'];
                    $g['name'] = $data['client_name'];
                    if (!empty($data['citizenship'])) $g['citizenship'] = $data['citizenship'];
                    if (!empty($data['address'])) $g['address'] = $data['address'];
                    $this->store->save('guests', $g);
                    break;
                }
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
        return $bookingId;
    }

    public function updateBookingStatus($bookingId, $status) {
        $booking = $this->store->findOne('bookings', $bookingId);
        if ($booking && is_array($booking)) {
            $booking['status'] = $status;
            $this->store->save('bookings', $booking);
            return true;
        }
        return false;
    }

    public function cancelBooking($bookingId) {
        return $this->updateBookingStatus($bookingId, 'cancelled');
    }

    public function updateBookingNotes($bookingId, $notes) {
        $booking = $this->store->findOne('bookings', $bookingId);
        if ($booking && is_array($booking)) {
            $booking['admin_notes'] = $notes;
            $this->store->save('bookings', $booking);
            return true;
        }
        return false;
    }
}
