<?php
/**
 * Test & Verification Runner for Memory Pages WordPress Plugin
 */

define('ABSPATH', __DIR__ . '/');

echo "========================================================\n";
echo "Automated PHP Syntax and Structure Verification Script\n";
echo "========================================================\n";

$files = array(
    'memory-pages/memory-pages.php',
    'memory-pages/includes/class-db.php',
    'memory-pages/includes/class-memorial.php',
    'memory-pages/includes/class-rewrites.php',
    'memory-pages/includes/class-qr.php',
    'memory-pages/includes/class-stats.php',
    'memory-pages/includes/class-elementor.php',
    'memory-pages/admin/class-admin.php',
    'memory-pages/admin/views/view-all.php',
    'memory-pages/admin/views/view-edit.php',
    'memory-pages/admin/views/view-requests.php',
    'memory-pages/admin/views/view-photos.php',
    'memory-pages/admin/views/view-relatives.php',
    'memory-pages/admin/views/view-qr.php',
    'memory-pages/admin/views/view-stats.php',
    'memory-pages/admin/views/view-logs.php',
    'memory-pages/admin/views/view-settings.php',
    'memory-pages/public/class-public.php',
    'memory-pages/templates/single-memorial.php',
);

$all_passed = true;

foreach ($files as $file) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        echo "MISSING FILE: {$file}\n";
        $all_passed = false;
        continue;
    }

    $output = array();
    $return_var = 0;
    exec("php -l " . escapeshellarg(__DIR__ . '/' . $file), $output, $return_var);

    if ($return_var === 0) {
        echo "Verifying: {$file}... OK\n";
    } else {
        echo "SYNTAX ERROR in {$file}: " . implode("\n", $output) . "\n";
        $all_passed = false;
    }
}

if ($all_passed) {
    echo "========================================================\n";
    echo "ALL " . count($files) . " FILES PASSED SYNTAX CHECK SUCCESSFULLY!\n";
    echo "========================================================\n";
    exit(0);
} else {
    echo "========================================================\n";
    echo "VERIFICATION FAILED!\n";
    echo "========================================================\n";
    exit(1);
}
