<?php

require_once __DIR__ . '/src/Database/JsonStore.php';
require_once __DIR__ . '/src/Models/Configuration.php';
require_once __DIR__ . '/src/Services/EncryptionService.php';
require_once __DIR__ . '/src/Services/SerialCommService.php';
require_once __DIR__ . '/src/Services/IntegrityService.php';

use App\Database\JsonStore;
use App\Models\Configuration;
use App\Services\EncryptionService;
use App\Services\SerialCommService;
use App\Services\IntegrityService;

header('Content-Type: application/json');

$store = new JsonStore(__DIR__ . '/data/device_memory.json');
$encryption = new EncryptionService();
$serial = new SerialCommService();
$integrity = new IntegrityService();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'check_system':
        echo json_encode([
            'integrity' => $integrity->checkIntegrity(),
            'hardware' => [
                'connected' => $serial->isHardwareConnected(),
                'port' => 'COM3'
            ]
        ]);
        break;

    case 'get_config':
        // Try real hardware first
        if ($serial->isHardwareConnected()) {
            $response = $serial->sendPacket(0x01); // CMD_READ_CONFIG
            if ($response['status'] === 'ok') {
                // In a real implementation, we would parse the hardware bytes
                // For now, we fall back to local store if hardware isn't fully responding
                echo json_encode($store->getData());
            } else {
                echo json_encode($store->getData());
            }
        } else {
            echo json_encode($store->getData());
        }
        break;

    case 'save_config':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            // Write to hardware if connected
            $hw_status = 'offline';
            if ($serial->isHardwareConnected()) {
                $hw_res = $serial->sendPacket(0x02, [0x01]); // CMD_WRITE_CONFIG
                $hw_status = ($hw_res['status'] === 'ok') ? 'success' : 'failed';
            }

            if ($store->setData($input)) {
                echo json_encode(['status' => 'success', 'hardware' => $hw_status]);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Failed to save to memory']);
            }
        }
        break;

    case 'export_file':
        $config = $store->getData();
        $format = $_GET['format'] ?? 'text';
        $content = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($format === 'encrypted') {
            $content = $encryption->encrypt($content);
            $filename = 'config.bin';
        } else {
            $filename = 'config.txt';
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $content;
        exit;

    case 'import_file':
        if ($method === 'POST' && isset($_FILES['config_file'])) {
            $content = file_get_contents($_FILES['config_file']['tmp_name']);

            if (!json_decode($content)) {
                try {
                    $decrypted = $encryption->decrypt($content);
                    if (json_decode($decrypted)) {
                        $content = $decrypted;
                    }
                } catch (Exception $e) {}
            }

            $data = json_decode($content, true);
            if ($data) {
                echo json_encode(['status' => 'success', 'data' => $data]);
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid file format']);
            }
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Action not found']);
        break;
}
