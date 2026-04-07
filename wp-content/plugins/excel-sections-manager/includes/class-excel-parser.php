<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

class ESM_Excel_Parser {

    public static function parse($filepath) {
        if (!file_exists($filepath)) {
            return array();
        }

        try {
            $spreadsheet = IOFactory::load($filepath);
            $sections = array();

            foreach ($spreadsheet->getSheetNames() as $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                $data = $sheet->toArray(null, true, true, true);

                // Header row
                $header = array_shift($data);
                $rows = array();

                foreach ($data as $row) {
                    $item = array();
                    foreach ($header as $colIndex => $colName) {
                        if (!empty($colName)) {
                            $item[strtolower(trim($colName))] = $row[$colIndex];
                        }
                    }
                    // Only add if at least one field is not empty
                    if (array_filter($item)) {
                        $rows[] = $item;
                    }
                }

                $sections[$sheetName] = $rows;
            }

            return $sections;
        } catch (Exception $e) {
            error_log('ESM Parser Error: ' . $e->getMessage());
            return array();
        }
    }
}
