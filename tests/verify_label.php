<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/LabelGenerator.php';

use App\LabelGenerator;

$generator = new LabelGenerator();

// Test Data Formatting
$testData = [
    'consignee' => 'Test Recipient',
    'gross_weight' => '10',
    'item_number' => '1/5'
];
$formatted = $generator->formatData($testData);

if ($formatted['consignee'] === 'Test Recipient' && $formatted['gross_weight'] === '10 kg') {
    echo "Data formatting test passed.\n";
} else {
    echo "Data formatting test failed.\n";
    print_r($formatted);
    exit(1);
}

// Test Barcode Generation
$barcodeSvg = $generator->generateBarcode('123456');
if (str_contains($barcodeSvg, '<svg') && str_contains($barcodeSvg, 'rect')) {
    echo "Barcode generation test passed.\n";
} else {
    echo "Barcode generation test failed.\n";
    exit(1);
}

echo "All tests passed successfully.\n";
