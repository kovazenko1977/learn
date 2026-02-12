<?php
namespace Sanatorium\Core\Services;

use Sanatorium\Core\Database\JsonStore;

class ServiceManager {
    private $store;

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function getAll() {
        return $this->store->findAll('extra_services');
    }
}
