<?php
namespace Managers;
use Core\JsonStore;

class OrderManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('orders');
    }

    public function getOrders() {
        return $this->store->findAll();
    }

    public function createOrder($data) {
        $data['status'] = 'новый';
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->store->create($data);
    }
}
