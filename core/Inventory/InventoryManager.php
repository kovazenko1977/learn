<?php
namespace Sanatorium\Core\Inventory;
use Sanatorium\Core\Database\JsonStore;

class InventoryManager {
    private $store;
    public function __construct(JsonStore $store) { $this->store = $store; }
    public function getStock() { return $this->store->findAll('inventory'); }
    public function updateStock($id, $qty) {
        $items = $this->getStock();
        foreach ($items as &$item) {
            if ($item['id'] == $id) { $item['quantity'] = $qty; break; }
        }
        $this->store->save('inventory', $items);
    }
}
