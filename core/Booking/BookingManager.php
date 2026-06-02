<?php
namespace Sanatorium\Core\Booking;

use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Helpers\AuditLogger;
use Sanatorium\Core\Helpers\TelegramNotifier;

class BookingManager {
    private $store;
    private $logger;

    public function __construct(JsonStore $store) {
        $this->store = $store;
        $this->logger = new AuditLogger($store);
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

    public function isAvailable($roomId, $checkIn, $checkOut, $excludeBookingId = null, $requestedGender = null, $requestedSeatType = 'main', $isFamily = false) {
        $start = strtotime($checkIn);
        $end = strtotime($checkOut);

        $room = $this->store->findOne('rooms', $roomId);
        if (!$room) return false;

        $roomClass = $this->store->findOne('room_classes', $room['room_class_id'] ?? 0);
        $bufferMinutes = (int)($roomClass['buffer_time'] ?? 0);
        $bufferSeconds = $bufferMinutes * 60;

        // Advanced Capacity: main + extra
        $totalMain = (int)($room['main_seats_count'] ?? 0);
        $totalExtra = (int)($room['extra_seats_count'] ?? 0);
        if ($totalMain === 0 && $totalExtra === 0) {
            $totalMain = (int)($room['capacity'] ?? 1);
        }

        $bookings = $this->store->findAll('bookings');
        $occupiedMain = 0;
        $occupiedExtra = 0;
        $existingGenders = [];
        $hasExistingFamily = false;

        if (is_array($bookings)) {
            foreach ($bookings as $b) {
                if (!is_array($b) || ($b['status'] ?? '') === 'cancelled') continue;
                if ($excludeBookingId && ($b['id'] ?? '') == $excludeBookingId) continue;

                if (($b['room_id'] ?? 0) == $roomId) {
                    $bStart = strtotime($b['check_in']) - $bufferSeconds;
                    $bEnd = strtotime($b['check_out']) + $bufferSeconds;

                    if ($start < $bEnd && $end > $bStart) {
                        if (($b['seat_type'] ?? 'main') === 'extra') {
                            $occupiedExtra++;
                        } else {
                            $occupiedMain++;
                        }
                        if (!empty($b['guest_gender'])) {
                            $existingGenders[] = $b['guest_gender'];
                        }
                        if (!empty($b['is_family'])) {
                            $hasExistingFamily = true;
                        }
                    }
                }
            }
        }

        // 1. Check Capacity based on requested seat type
        if ($requestedSeatType === 'extra') {
            if ($occupiedExtra >= $totalExtra) return false;
        } else {
            if ($occupiedMain >= $totalMain) return false;
        }

        // 2. Check Gender Matching
        if ($isFamily || $hasExistingFamily) {
            return true; // Family bypasses gender rules
        }

        $uniqueGenders = array_unique($existingGenders);
        if ($requestedGender && count($uniqueGenders) > 0) {
            if (!in_array($requestedGender, $uniqueGenders)) {
                return false;
            }
        }

        return true;
    }

    public function createBooking($data) {
        if (empty($data['check_in']) || empty($data['check_out']) || empty($data['room_id']) || empty($data['phone'])) {
            return false;
        }

        $isFamily = !empty($data['is_family']);

        if (!$this->isAvailable($data['room_id'], $data['check_in'], $data['check_out'], null, $data['guest_gender'] ?? null, $data['seat_type'] ?? 'main', $isFamily)) {
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

        $this->logger->log('CREATE', 'booking', $bookingId, "Новое бронирование для " . ($data['client_name'] ?? 'N/A'));

        $notifier = new TelegramNotifier();
        $message = "🔔 <b>Новое бронирование!</b>\n\n";
        $message .= "👤 Гость: " . ($data['client_name'] ?? 'N/A') . "\n";
        $message .= "📞 Тел: " . ($data['phone'] ?? '') . "\n";
        $message .= "📅 Период: " . ($data['check_in'] ?? '') . " - " . ($data['check_out'] ?? '') . "\n";
        $message .= "💰 Сумма: " . number_format($data['total_price'] ?? 0, 0, ',', ' ') . " ₽";
        $notifier->sendMessage($message);

        return $bookingId;
    }

    public function updateBooking($id, $data) {
        $existing = $this->store->findOne('bookings', $id);
        if (!$existing) return false;

        $roomId = $data['room_id'] ?? $existing['room_id'];
        $checkIn = $data['check_in'] ?? $existing['check_in'];
        $checkOut = $data['check_out'] ?? $existing['check_out'];
        $gender = $data['guest_gender'] ?? $existing['guest_gender'] ?? null;
        $seatType = $data['seat_type'] ?? $existing['seat_type'] ?? 'main';
        $isFamily = isset($data['is_family']) ? !empty($data['is_family']) : !empty($existing['is_family']);

        if (!$this->isAvailable($roomId, $checkIn, $checkOut, $id, $gender, $seatType, $isFamily)) {
            return false;
        }

        $merged = array_merge($existing, $data);
        $merged['total_price'] = $this->calculatePrice($merged);

        $result = $this->store->save('bookings', $merged);
        if ($result) {
            $this->logger->log('UPDATE', 'booking', $id, "Обновление данных бронирования");
        }
        return $result;
    }

    public function deleteBooking($id) {
        $this->logger->log('DELETE', 'booking', $id, "Удаление бронирования");
        return $this->store->delete('bookings', $id);
    }

    public function updateBookingStatus($bookingId, $status) {
        $booking = $this->store->findOne('bookings', $bookingId);
        if ($booking && is_array($booking)) {
            $oldStatus = $booking['status'] ?? 'unknown';
            $booking['status'] = $status;
            $this->store->save('bookings', $booking);
            $this->logger->log('STATUS_CHANGE', 'booking', $bookingId, "Смена статуса: $oldStatus -> $status");
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
        $seatType = $booking['seat_type'] ?? 'main';
        $isFamily = !empty($booking['is_family']);

        if (!$this->isAvailable($newRoomId, $checkIn, $checkOut, $bookingId, $gender, $seatType, $isFamily)) {
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

    public function getRoomOccupancy($roomId, $checkIn, $checkOut, $excludeBookingId = null) {
        $start = strtotime($checkIn);
        $end = strtotime($checkOut);

        $room = $this->store->findOne('rooms', $roomId);
        if (!$room) return null;

        $totalMain = (int)($room['main_seats_count'] ?? 0);
        $totalExtra = (int)($room['extra_seats_count'] ?? 0);

        // Fallback for simple rooms
        if ($totalMain === 0 && $totalExtra === 0) {
            $totalMain = (int)($room['capacity'] ?? 1);
        }

        $bookings = $this->store->findAll('bookings');
        $occupiedMain = 0;
        $occupiedExtra = 0;
        $genders = [];

        if (is_array($bookings)) {
            foreach ($bookings as $b) {
                if (!is_array($b) || ($b['status'] ?? '') === 'cancelled') continue;
                if ($excludeBookingId && ($b['id'] ?? '') == $excludeBookingId) continue;

                if (($b['room_id'] ?? 0) == $roomId) {
                    $bStart = strtotime($b['check_in']);
                    $bEnd = strtotime($b['check_out']);

                    if ($start < $bEnd && $end > $bStart) {
                        if (($b['seat_type'] ?? 'main') === 'extra') {
                            $occupiedExtra++;
                        } else {
                            $occupiedMain++;
                        }
                        if (!empty($b['guest_gender'])) {
                            $genders[] = $b['guest_gender'];
                        }
                    }
                }
            }
        }

        return [
            'main_total' => $totalMain,
            'main_occupied' => $occupiedMain,
            'main_free' => max(0, $totalMain - $occupiedMain),
            'extra_total' => $totalExtra,
            'extra_occupied' => $occupiedExtra,
            'extra_free' => max(0, $totalExtra - $occupiedExtra),
            'genders' => array_unique($genders)
        ];
    }
}
