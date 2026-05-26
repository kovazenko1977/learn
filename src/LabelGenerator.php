<?php

namespace App;

use Picqer\Barcode\BarcodeGeneratorSVG;

class LabelGenerator
{
    private $barcodeGenerator;

    public function __construct()
    {
        $this->barcodeGenerator = new BarcodeGeneratorSVG();
    }

    /**
     * Generates a barcode SVG string for the given data.
     *
     * @param string $data
     * @return string
     */
    public function generateBarcode(string $data): string
    {
        if (empty($data)) {
            return '';
        }
        return $this->barcodeGenerator->getBarcode($data, $this->barcodeGenerator::TYPE_CODE_128, 2, 50);
    }

    /**
     * Formats label data for display.
     *
     * @param array $data
     * @return array
     */
    public function formatData(array $data): array
    {
        return [
            'consignee' => $data['consignee'] ?? '',
            'destination' => $data['destination'] ?? '',
            'package_count' => $data['package_count'] ?? '',
            'item_number' => $data['item_number'] ?? '',
            'gross_weight' => isset($data['gross_weight']) ? $data['gross_weight'] . ' kg' : '',
            'net_weight' => isset($data['net_weight']) ? $data['net_weight'] . ' kg' : '',
            'dimensions' => $data['dimensions'] ?? '',
            'barcode_data' => $data['barcode_data'] ?? $data['item_number'] ?? ''
        ];
    }
}
