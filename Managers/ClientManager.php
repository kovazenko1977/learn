<?php
namespace Managers;
use Core\JsonStore;

class ClientManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('clients');
    }

    public function getClients() {
        return $this->store->findAll();
    }

    public function createClient($data) {
        return $this->store->create($data);
    }
}
