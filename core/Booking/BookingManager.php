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
        $bookingType = $roomClass['booking_type'] ?? 'daily';

        $totalPrice = 0;
        if ($bookingType === 'hourly') {
            $hours = ceil(max(3600, ($checkOut - $checkIn)) / 3600);
            $totalPrice = (float)($room['price_per_hour'] ?? ($room['price_per_day'] / 24)) * $hours;
        } else {
            $days = ceil(max(86400, ($checkOut - $checkIn)) / 86400);

            // Tiered pricing logic
            $seatType = $data['seat_type'] ?? 'main';
            $pricePerDay = ($seatType === 'extra')
                ? (float)($room['price_extra'] ?? ($room['price_per_day'] * 0.7))
                : (float)($room['price_main'] ?? $room['price_per_day']);

            $totalPrice = $pricePerDay * $days;
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

    public function isAvailable($roomId, $checkIn, $checkOut, $excludeBookingId = null, $requestedGender = null) {
        $start = strtotime($checkIn);
        $end = strtotime($checkOut);

        $room = $this->store->findOne('rooms', $roomId);
        if (!$room) return false;

        $roomClass = $this->store->findOne('room_classes', $room['room_class_id'] ?? 0);
        $bufferMinutes = (int)($roomClass['buffer_time'] ?? 0);
        $bufferSeconds = $bufferMinutes * 60;

        // Advanced Capacity: main + extra
        $totalCapacity = (int)($room['main_seats_count'] ?? 0) + (int)($room['extra_seats_count'] ?? 0);
        if ($totalCapacity <= 0) $totalCapacity = (int)($room['capacity'] ?? 1);

        $bookings = $this->store->findAll('bookings');
        $occupiedSeatsAtTime = 0;
        $existingGender = null;

        if (is_array($bookings)) {
            foreach ($bookings as $b) {
                if (!is_array($b) || ($b['status'] ?? '') === 'cancelled') continue;
                if ($excludeBookingId && ($b['id'] ?? '') == $excludeBookingId) continue;

                if (($b['room_id'] ?? 0) == $roomId) {
                    $bStart = strtotime($b['check_in']) - $bufferSeconds;
                    $bEnd = strtotime($b['check_out']) + $bufferSeconds;

                    if ($start < $bEnd && $end > $bStart) {
                        // Room is partially or fully occupied during this period
                        $occupiedSeatsAtTime++;
                        if (isset($b['guest_gender'])) {
                            $existingGender = $b['guest_gender'];
                        }
                    }
                }
            }
        }

        // 1. Check Capacity
        if ($occupiedSeatsAtTime >= $totalCapacity) {
            return false;
        }

        // 2. Check Gender Matching (Only if requested gender is provided)
        if ($requestedGender && $existingGender && $existingGender !== $requestedGender) {
            return false;
        }

        return true;
    }

    public function createBooking($data) {
        if (empty($data['check_in']) || empty($data['check_out']) || empty($data['room_id']) || empty($data['phone'])) {
            return false;
        }

        if (!$this->isAvailable($data['room_id'], $data['check_in'], $data['check_out'], null, $data['guest_gender'] ?? null)) {
            return false;
        }

        // Handle Guest association
        $guestsData = [
            'name' => $data['client_name'] ?? 'N/A',
            'phone' => $data['phone'],
            'gender' => $data['guest_gender'] ?? 'unknown',
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
                    $g['gender'] = $guestsData['gender'];
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

    public function updateBooking($id, $data) {
        $existing = $this->store->findOne('bookings', $id);
        if (!$existing) return false;

        $roomId = $data['room_id'] ?? $existing['room_id'];
        $checkIn = $data['check_in'] ?? $existing['check_in'];
        $checkOut = $data['check_out'] ?? $existing['check_out'];
        $gender = $data['guest_gender'] ?? $existing['guest_gender'] ?? null;

        if (!$this->isAvailable($roomId, $checkIn, $checkOut, $id, $gender)) {
            return false;
        }

        $merged = array_merge($existing, $data);
        $merged['total_price'] = $this->calculatePrice($merged);

        return $this->store->save('bookings', $merged);
    }

    public function deleteBooking($id) {
        return $this->store->delete('bookings', $id);
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

    public function relocateGuest($bookingId, $newRoomId, $reason) {
        $booking = $this->store->findOne('bookings', $bookingId);
        if (!$booking) return false;

        $checkIn = $booking['check_in'];
        $checkOut = $booking['check_out'];
        $gender = $booking['guest_gender'] ?? null;

        if (!$this->isAvailable($newRoomId, $checkIn, $checkOut, $bookingId, $gender)) {
            return false;
        }

        $oldRoom = $this->store->findOne('rooms', $booking['room_id']);
        $newRoom = $this->store->findOne('rooms', $newRoomId);

        $oldRoomNum = $oldRoom['room_number'] ?? 'ID '.$booking['room_id'];
        $newRoomNum = $newRoom['room_number'] ?? 'ID '.$newRoomId;

        $booking['room_id'] = $newRoomId;

        $timestamp = date('d.m.Y H:i');
        $logEntry = "\n[$timestamp] Переселение: из $oldRoomNum в $newRoomNum. Причина: $reason";
        $booking['admin_notes'] = ($booking['admin_notes'] ?? '') . $logEntry;

        // Recalculate price if room class changed
        $booking['total_price'] = $this->calculatePrice($booking);

        return $this->store->save('bookings', $booking);
    }
}
