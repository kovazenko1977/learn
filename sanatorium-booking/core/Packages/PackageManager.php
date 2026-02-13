<?php
namespace Sanatorium\Core\Packages;
use Sanatorium\Core\Database\JsonStore;
class PackageManager {
    private $store;
    public function __construct(JsonStore $store) { $this->store = $store; }
    public function getAll() { return $this->store->findAll('packages'); }
}
