<?php
namespace App\Services;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\BarcodeGeneratorPNG;
class BarcodeService {
    public function generateSVG($code, $type) {
        $generator = new BarcodeGeneratorSVG();
        $barcodeType = $this->mapType($type);
        return $generator->getBarcode($code, $barcodeType);
    }
    public function generatePNG($code, $type) {
        $generator = new BarcodeGeneratorPNG();
        $barcodeType = $this->mapType($type);
        return $generator->getBarcode($code, $barcodeType);
    }
    private function mapType($type) {
        switch (strtoupper($type)) {
            case 'EAN13':
            case 'EAN-13':
                return BarcodeGeneratorSVG::TYPE_EAN_13;
            case 'CODE128':
                return BarcodeGeneratorSVG::TYPE_CODE_128;
            default:
                return BarcodeGeneratorSVG::TYPE_CODE_128;
        }
    }
    public function validateEAN13($code) {
        if (!preg_match('/^\d{13}$/', $code)) return false;
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $code[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        $check = (10 - ($sum % 10)) % 10;
        return $check == $code[12];
    }
}
