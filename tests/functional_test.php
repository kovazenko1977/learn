<?php

require_once __DIR__ . '/../src/Database/JsonStore.php';
require_once __DIR__ . '/../src/Models/Configuration.php';

use App\Database\JsonStore;
use App\Models\Configuration;

$storeFile = __DIR__ . '/../data/test_memory.json';
if (file_exists($storeFile)) unlink($storeFile);

$store = new JsonStore($storeFile);
$config = new Configuration();

// 1. Test adding a device
$config->addDevice(['address' => 5, 'type' => 'С2000-4', 'version' => '2.01']);
$data = $config->getData();
assert(count($data['devices']) === 1);
assert($data['devices'][0]['address'] === 5);
echo "Add device: PASS\n";

// 2. Test adding a user
$config->addUser(['name' => 'Admin', 'password' => '123', 'role' => 'admin']);
$data = $config->getData();
assert(count($data['users']) === 1);
assert($data['users'][0]['name'] === 'Admin');
echo "Add user: PASS\n";

// 3. Test saving and loading from store
$store->setData($config->getData());
$loadedData = $store->getData();
assert($loadedData['devices'][0]['address'] === 5);
assert($loadedData['users'][0]['name'] === 'Admin');
echo "Store save/load: PASS\n";

// 4. Test removing device
$config->removeDevice(0);
assert(count($config->getData()['devices']) === 0);
echo "Remove device: PASS\n";

if (file_exists($storeFile)) unlink($storeFile);
echo "\nALL FUNCTIONAL TESTS PASSED\n";
