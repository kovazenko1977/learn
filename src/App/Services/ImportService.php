<?php
namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Product;
use Exception;

class ImportService {
    public function importFromExcel($filePath, $categoryId = null) {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $productModel = new Product();
            $importedCount = 0;

            if (count($rows) < 2) return 0;

            $headers = array_shift($rows);

            foreach ($rows as $row) {
                if (empty($row[0])) continue;

                $data = [
                    'name' => $row[0] ?? '',
                    'composition' => $row[1] ?? '',
                    'gost' => $row[2] ?? '',
                    'manufacturer' => $row[3] ?? '',
                    'volume' => $row[4] ?? '',
                    'alcohol' => $row[5] ?? '',
                    'sugar' => $row[6] ?? '',
                    'expiration' => $row[7] ?? '',
                    'barcode' => $row[8] ?? '',
                    'category_id' => $categoryId,
                    'extra_data' => null
                ];

                $productModel->create($data);
                $importedCount++;
            }

            return $importedCount;
        } catch (Exception $e) {
            error_log("Import error: " . $e->getMessage());
            return 0;
        }
    }
}
