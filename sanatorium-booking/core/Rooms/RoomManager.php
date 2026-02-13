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
        foreach ($classes as $c) $classMap[$c['id']] = $c['name'];

        $availableRooms = [];
        foreach ($allRooms as $room) {
            if ($persons > 0 && $room['capacity'] < $persons) {
                continue;
            }

            $isAvailable = true;
            foreach ($calendar as $entry) {
                if ($entry['room_id'] == $room['id']) {
                    $entryDate = strtotime($entry['date']);
                    $start = strtotime($checkIn);
                    $end = strtotime($checkOut);

                    if ($entryDate >= $start && $entryDate < $end) {
                        if ($entry['status'] !== 'free') {
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
        return $availableRooms;
    }
}
