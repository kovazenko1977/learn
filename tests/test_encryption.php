<?php

require_once __DIR__ . '/../src/Services/EncryptionService.php';

use App\Services\EncryptionService;

$encryption = new EncryptionService();
$originalData = json_encode(['test' => 'data', 'nested' => ['val' => 123]], JSON_UNESCAPED_UNICODE);

$encrypted = $encryption->encrypt($originalData);
$decrypted = $encryption->decrypt($encrypted);

echo "Original: $originalData\n";
echo "Encrypted (Base64): $encrypted\n";
echo "Decrypted: $decrypted\n";

if ($originalData === $decrypted) {
    echo "SUCCESS: Decryption matches original data.\n";
} else {
    echo "FAILURE: Decryption mismatch.\n";
    exit(1);
}
