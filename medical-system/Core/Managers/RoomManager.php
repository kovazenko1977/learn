<?php

namespace Medical\Core\Managers;

use Medical\Core\JsonStore;

class RoomManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('rooms');
    }

    public function getAll() {
        return $this->store->getAll();
    }

    public function getById($id) {
        return $this->store->findById($id);
    }

    public function add($data) {
        $data['id'] = uniqid();
        $this->store->add($data);
        (new LogManager())->log('Добавление номера в фонд', ['name' => $data['name']]);
        return $data['id'];
    }

    public function update($id, $data) {
        $res = $this->store->updateById($id, $data);
        if ($res) {
            (new LogManager())->log('Обновление данных номера', ['id' => $id]);
        }
        return $res;
    }

    public function delete($id) {
        $res = $this->store->deleteById($id);
        if ($res) {
            (new LogManager())->log('Удаление номера из фонда', ['id' => $id]);
        }
        return $res;
    }

    public function getBasePrice($roomId, $date = null) {
        $room = $this->getById($roomId);
        if (!$room) return 0;

        $basePrice = (float)$room['base_price'];

        // Season rules
        $rulesStore = new JsonStore('pricing_rules');
        $rules = $rulesStore->getAll();

        if ($date) {
            $timestamp = strtotime($date);
            foreach ($rules as $rule) {
                $start = strtotime($rule['start_date']);
                $end = strtotime($rule['end_date']);
                if ($timestamp >= $start && $timestamp <= $end) {
                    if ($rule['type'] === 'fixed') {
                        return (float)$rule['value'];
                    } else if ($rule['type'] === 'multiplier') {
                        return $basePrice * (float)$rule['value'];
                    }
                }
            }
        }

        return $basePrice;
    }
}
