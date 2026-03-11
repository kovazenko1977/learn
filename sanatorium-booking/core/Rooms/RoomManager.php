<?php
namespace Sanatorium\Core\Rooms;

use Sanatorium\Core\Database\JsonStore;

class RoomManager {
    private $store;

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function getAllRooms() {
        return $this->store->findAll('rooms');
    }

    public function getAvailableRooms($checkIn, $checkOut, $persons = 0) {
        $allRooms = $this->getAllRooms();
        $calendar = $this->store->findAll('room_calendar');
        $classes = $this->store->findAll('room_classes');
        $classMap = [];
        if (is_array($classes)) {
            foreach ($classes as $c) {
                if (is_array($c) && isset($c['id'])) $classMap[$c['id']] = $c['name'] ?? 'N/A';
            }
        }

        $availableRooms = [];
        if (is_array($allRooms)) {
            foreach ($allRooms as $room) {
                if (!is_array($room)) continue;
                if ($persons > 0 && ($room['capacity'] ?? 0) < $persons) {
                    continue;
                }

                $isAvailable = true;
                if (is_array($calendar)) {
                    foreach ($calendar as $entry) {
                        if (is_array($entry) && isset($entry['room_id']) && $entry['room_id'] == $room['id']) {
                            $entryDate = strtotime($entry['date'] ?? '');
                            $start = strtotime($checkIn);
                            $end = strtotime($checkOut);

                            if ($entryDate && $start && $end && $entryDate >= $start && $entryDate < $end) {
                                if (($entry['status'] ?? 'free') !== 'free') {
                                    $isAvailable = false;
                                    break;
                                }
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
