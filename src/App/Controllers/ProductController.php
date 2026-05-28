<?php
namespace App\Controllers;

use App\Models\JsonStore;

class ProductController {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('products');
    }

    public function index() {
        return $this->store->getAll();
    }

    public function store($data) {
        $id = $this->store->add($data);
        return ['status' => 'success', 'id' => $id];
    }
}
