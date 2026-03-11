<?php

namespace Medical\Core\Managers;

use Medical\Core\JsonStore;

class BookingManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('bookings');
    }

    public function getAll() {
        return $this->store->getAll();
    }

    public function getById($id) {
        return $this->store->findById($id);
    }

    public function create($data) {
        $data['id'] = uniqid();
        $data['status'] = $data['status'] ?? 'preliminary'; // preliminary, confirmed, checked_in, checked_out, cancelled
        $data['created_at'] = date('Y-m-d H:i:s');

        // Integration with Patient Registry
        $pm = new PatientManager();
        $existing = $pm->findByNameOrPhone($data['guest_name'], $data['guest_phone']);
        if ($existing) {
            $data['patient_id'] = $existing['id'];
        } else {
            // Create new patient record
            $patientId = $pm->add([
                'name' => $data['guest_name'],
                'phone' => $data['guest_phone'],
                'birth_date' => $data['guest_birth_date'] ?? '',
                'card_number' => $data['guest_card_number'] ?? '',
                'residence' => $data['guest_residence'] ?? '',
                'extra_info' => 'Создан автоматически при бронировании'
            ]);
            $data['patient_id'] = $patientId;
        }

        // Calculate total cost
        $rm = new RoomManager();
        $total = 0;
        $start = new \DateTime($data['check_in']);
        $end = new \DateTime($data['check_out']);
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end);

        foreach ($period as $dt) {
            $total += $rm->getBasePrice($data['room_id'], $dt->format('Y-m-d'));
        }
        $data['total_cost'] = $total;

        $this->store->add($data);
        (new LogManager())->log('Создание бронирования', ['id' => $data['id'], 'guest' => $data['guest_name']]);
        return $data['id'];
    }

    public function update($id, $data) {
        $res = $this->store->updateById($id, $data);
        if ($res) {
            (new LogManager())->log('Обновление бронирования', ['id' => $id]);
        }
        return $res;
    }

    public function getByRoom($roomId, $start, $end) {
        $bookings = $this->getAll();
        return array_filter($bookings, function($b) use ($roomId, $start, $end) {
            return $b['room_id'] === $roomId &&
                   $b['status'] !== 'cancelled' &&
                   $b['check_in'] < $end &&
                   $b['check_out'] > $start;
        });
    }

    public function getByPatient($patientId) {
        $bookings = $this->getAll();
        return array_filter($bookings, function($b) use ($patientId) {
            return ($b['patient_id'] ?? '') === $patientId;
        });
    }

    public function getActiveByPatient($patientId) {
        $bookings = $this->getByPatient($patientId);
        $today = date('Y-m-d');
        foreach ($bookings as $b) {
            if ($b['status'] !== 'cancelled' && $b['status'] !== 'checked_out') {
                if ($today >= $b['check_in'] && $today < $b['check_out']) {
                    return $b;
                }
            }
        }
        // If not strictly within dates, check if it's a confirmed future booking
        foreach ($bookings as $b) {
            if ($b['status'] === 'confirmed' || $b['status'] === 'preliminary') {
                if ($b['check_in'] >= $today) {
                    return $b;
                }
            }
        }
        return null;
    }

    public function isAvailable($roomId, $start, $end, $excludeId = null) {
        $bookings = $this->getAll();
        foreach ($bookings as $b) {
            if ($b['id'] === $excludeId) continue;
            if ($b['room_id'] === $roomId && $b['status'] !== 'cancelled') {
                if ($b['check_in'] < $end && $b['check_out'] > $start) {
                    return false;
                }
            }
        }
        return true;
    }
}
