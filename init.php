<?php
require_once 'includes/Storage.php';

// Ensure data directory is initialized
Storage::init();

// Clean existing data for a fresh start if requested (optional)
// Storage::write('products', []);
// Storage::write('clients', []);
// Storage::write('orders', []);

echo "B2B Vitrina initialized.\n";
