<?php
namespace Managers;
use Core\JsonStore;

class PriceListManager {
    private $store;
    private $productStore;

    public function __construct() {
        $this->store = new JsonStore('price_lists');
        $this->productStore = new JsonStore('products');
    }

    public function getPriceLists() {
        return $this->store->findAll();
    }

    public function createPriceList($data) {
        return $this->store->create([
            'name' => $data['name'],
            'discount' => (float)($data['discount'] ?? 0),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function generate($id) {
        $priceList = $this->store->findOne($id);
        if (!$priceList) return null;

        $products = $this->productStore->findAll();
        $items = [];
        foreach ($products as $p) {
            $items[] = [
                'name' => $p['name'],
                'sku' => $p['sku'],
                'base_price' => $p['price'],
                'discounted_price' => round($p['price'] * (1 - $priceList['discount'] / 100), 2)
            ];
        }

        return [
            'price_list_name' => $priceList['name'],
            'discount' => $priceList['discount'],
            'items' => $items,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
}
