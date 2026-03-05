<?php
namespace Managers;
use Core\JsonStore;

class ProductManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('products');
    }

    public function getProducts() {
        return $this->store->findAll();
    }

    public function addProduct($data) {
        return $this->store->create($data);
    }

    public function getProduct($id) {
        return $this->store->findOne($id);
    }
}
