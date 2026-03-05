<?php
namespace Includes;

class ExportHelper {
    public static function arrayToCsv($data, $filename = 'export.csv') {
        if (!is_dir(__DIR__ . '/../Uploads')) {
            mkdir(__DIR__ . '/../Uploads', 0777, true);
        }

        $filePath = __DIR__ . '/../Uploads/' . $filename;
        $fp = fopen($filePath, 'w');

        // UTF-8 BOM for Excel
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

        if (!empty($data)) {
            fputcsv($fp, array_keys(reset($data)), ';');
            foreach ($data as $row) {
                fputcsv($fp, $row, ';');
            }
        }

        fclose($fp);
        return 'Uploads/' . $filename;
    }
}
