<?php
namespace Sanatorium\Core\Rooms;

use Sanatorium\Core\Database\JsonStore;

class RoomManager {
    private $store;

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function getAllRooms() {
        $rooms = $this->store->findAll('rooms');
        // Initialize housekeeping status if not present
        $changed = false;
        foreach ($rooms as &$room) {
            if (!isset($room['housekeeping'])) {
                $room['housekeeping'] = 'clean'; // clean, dirty, cleaning
                $changed = true;
            }
        }
        if ($changed) {
            $this->store->save('rooms', $rooms);
        }
        return $rooms;
    }

    public function updateHousekeeping($roomId, $status) {
        $rooms = $this->getAllRooms();
        foreach ($rooms as &$room) {
            if ($room['id'] == $roomId) {
                $room['housekeeping'] = $status;
                break;
            }
        }
        $this->store->save('rooms', $rooms);
    }

    public function getAvailableRooms($checkIn, $checkOut, $persons = 0) {
        $allRooms = $this->getAllRooms();
        $bookings = $this->store->findAll('bookings');
        $classes = $this->store->findAll('room_classes');
        $classMap = [];
        if (is_array($classes)) {
            foreach ($classes as $c) {
                if (is_array($c) && isset($c['id'])) $classMap[$c['id']] = $c['name'] ?? 'N/A';
            }
        }

        $availableRooms = [];
        $start = strtotime($checkIn);
        $end = strtotime($checkOut);

        if (!$start || !$end) return [];

        if (is_array($allRooms)) {
            foreach ($allRooms as $room) {
                if (!is_array($room)) continue;
                if ($persons > 0 && ($room['capacity'] ?? 0) < $persons) {
                    continue;
                }

                $isAvailable = true;
                if (is_array($bookings)) {
                    foreach ($bookings as $b) {
                        if (!is_array($b) || ($b['status'] ?? '') === 'cancelled') continue;
                        if (($b['room_id'] ?? 0) == $room['id']) {
                            $bStart = strtotime($b['check_in']);
                            $bEnd = strtotime($b['check_out']);

                            if ($start < $bEnd && $end > $bStart) {
                                $isAvailable = false;
                                break;
                            }
                        }
                    }
                }

                if ($isAvailable) {
                    $room['room_class_name'] = $classMap[$room['room_class_id'] ?? 0] ?? 'N/A';
                    $availableRooms[] = $room;
                }
            }
        }
        return $availableRooms;
    }
}
