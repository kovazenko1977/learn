<?php
namespace App\Controllers;

use App\Models\JsonStore;

class TemplateController {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('templates');
    }

    public function index() {
        return $this->store->getAll();
    }

    public function store($data) {
        $id = $this->store->add($data);
        return ['status' => 'success', 'id' => $id];
    }
}
