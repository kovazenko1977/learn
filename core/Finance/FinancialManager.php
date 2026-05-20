<?php
namespace Sanatorium\Core\Finance;
use Sanatorium\Core\Database\JsonStore;

class FinancialManager {
    private $store;
    public function __construct(JsonStore $store) { $this->store = $store; }
    public function addExpense($data) {
        $expenses = $this->store->findAll('expenses');
        $data['id'] = time();
        $data['date'] = date('Y-m-d H:i:s');
        $expenses[] = $data;
        $this->store->save('expenses', $expenses);
    }
    public function getExpenses() { return $this->store->findAll('expenses'); }
}
