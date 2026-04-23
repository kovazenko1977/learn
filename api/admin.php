<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';

$settingsStorage = new Storage('settings.json');
$settings = $settingsStorage->getAll();

$action = $_GET['action'] ?? '';

// Simple authentication check
if ($action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    $pass = $input['password'] ?? '';
    $hash = $settings['admin_password_hash'] ?? '';

    if (password_verify($pass, $hash)) {
        $_SESSION['admin_logged_in'] = true;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Неверный пароль']);
    }
    exit;
}

    if ($action === 'logout') {
        session_destroy();
        echo json_encode(['success' => true]);
        exit;
    }

if (!($_SESSION['admin_logged_in'] ?? false)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$regStorage = new Storage('registrations.json');

switch ($action) {
    case 'get_data':
        $cleanSettings = $settings;
        unset($cleanSettings['admin_password_hash']);
        echo json_encode([
            'registrations' => $regStorage->getAll(),
            'settings' => $cleanSettings
        ]);
        break;

    case 'update_settings':
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            // Protect password hash from being overwritten by public data if not intended
            if (isset($input['admin_password_hash'])) {
                 unset($input['admin_password_hash']);
            }

            // Merge settings
            $newSettings = array_merge($settings, $input);
            $settingsStorage->save($newSettings);
            echo json_encode(['success' => true]);
        }
        break;

    case 'delete_registration':
        $id = $_GET['id'] ?? '';
        $regs = $regStorage->getAll();
        $regs = array_values(array_filter($regs, function($r) use ($id) {
            return $r['id'] !== $id;
        }));
        $regStorage->save($regs);
        echo json_encode(['success' => true]);
        break;

    case 'approve_registration':
        $id = $_GET['id'] ?? '';
        $discount = (int)($_GET['discount'] ?? 0);
        $regs = $regStorage->getAll();
        foreach ($regs as &$reg) {
            if ($reg['id'] === $id) {
                $reg['status'] = 'approved';
                $reg['discount'] = $discount;
            }
        }
        $regStorage->save($regs);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
