<?php
namespace Sanatorium\Core\Guests;

use Sanatorium\Core\Database\JsonStore;

class GuestManager {
    private $store;

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function getAll() {
        return $this->store->findAll('guests');
    }

    public function getById($id) {
        return $this->store->findOne('guests', $id);
    }

    public function findByPhone($phone) {
        $guests = $this->getAll();
        foreach ($guests as $g) {
            if ($g['phone'] === $phone) return $g;
        }
        return null;
    }

    public function save($data) {
        return $this->store->save('guests', $data);
    }

    public function delete($id) {
        return $this->store->delete('guests', $id);
    }

    public function getStayHistory($guestId, $bookings) {
        return array_filter($bookings, function($b) use ($guestId) {
            return isset($b['guest_id']) && $b['guest_id'] == $guestId;
        });
    }

    public function isCurrentlyStaying($guestId, $bookings) {
        $today = date('Y-m-d');
        foreach ($bookings as $b) {
            if (isset($b['guest_id']) && $b['guest_id'] == $guestId && ($b['status'] ?? '') === 'confirmed') {
                $checkIn = $b['check_in'] ?? '';
                $checkOut = $b['check_out'] ?? '';
                if ($checkIn && $checkOut && $today >= $checkIn && $today < $checkOut) {
                    return true;
                }
            }
        }
        return false;
    }
}
