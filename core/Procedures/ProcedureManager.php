<?php
namespace Sanatorium\Core\Procedures;
use Sanatorium\Core\Database\JsonStore;
class ProcedureManager {
    private $store;
    public function __construct(JsonStore $store) { $this->store = $store; }
    public function getAll() { return $this->store->findAll('procedures'); }
}
