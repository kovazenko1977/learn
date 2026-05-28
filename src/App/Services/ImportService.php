<?php
namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportService {
    public function importFromExcel($filepath) {
        $spreadsheet = IOFactory::load($filepath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $header = array_shift($rows);
        $products = [];

        foreach ($rows as $row) {
            $products[] = array_combine($header, $row);
        }

        return $products;
    }

    public function substitute($text, $productData) {
        $map = [
            '{PRODUCT}' => $productData['name'] ?? '',
            '{COMPOSITION}' => $productData['composition'] ?? '',
            '{GOST}' => $productData['gost'] ?? '',
            '{MANUFACTURER}' => $productData['manufacturer'] ?? '',
            '{VOLUME}' => $productData['volume'] ?? '',
            '{ALCOHOL}' => $productData['alcohol'] ?? '',
            '{EXPIRATION}' => $productData['expiration'] ?? '',
            '{BARCODE}' => $productData['barcode'] ?? '',
            '{DATE}' => date('d.m.Y'),
            '{BATCH}' => $productData['batch'] ?? '000001',
        ];

        return strtr($text, $map);
    }
}
