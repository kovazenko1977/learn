<?php
namespace App\Packages;
use App\Database\JsonStore;
class PackageManager {
    private $store;
    public function __construct(JsonStore $store) { $this->store = $store; }
    public function getAll() { return $this->store->findAll('packages'); }
}
