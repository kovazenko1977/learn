<?php
namespace App\Planning;

use App\Database\JsonStore;

class PlanningManager {
    private $store;

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function getAll() {
        return $this->store->findAll('plans');
    }

    public function getByDate($date) {
        $plans = $this->getAll();
        return array_filter($plans, function($p) use ($date) {
            return ($p['date'] ?? '') === $date;
        });
    }

    public function save($data) {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $data['status'] = $data['status'] ?? 'pending';
        return $this->store->save('plans', $data);
    }

    public function delete($id) {
        return $this->store->delete('plans', $id);
    }
}
