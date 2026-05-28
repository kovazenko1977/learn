<?php
use PHPUnit\Framework\TestCase;
use App\Services\ImportService;

class ImportServiceTest extends TestCase {
    public function testSubstitution() {
        $service = new ImportService();
        $product = ['name' => 'Чиназес', 'volume' => '0.5'];
        $result = $service->substitute('Продукт: {PRODUCT}, Объем: {VOLUME}л', $product);
        $this->assertEquals('Продукт: Чиназес, Объем: 0.5л', $result);
    }
}
