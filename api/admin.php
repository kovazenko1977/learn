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
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        echo json_encode(['success' => true, 'csrf_token' => $_SESSION['csrf_token']]);
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

// CSRF check for write actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($action !== 'get_data' && $action !== 'logout')) {
    $clientToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_GET['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $clientToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF token mismatch']);
        exit;
    }
}

$regStorage = new Storage('registrations.json');

switch ($action) {
    case 'get_data':
        $cleanSettings = $settings;
        unset($cleanSettings['admin_password_hash']);
        echo json_encode([
            'registrations' => $regStorage->getAll(),
            'settings' => $cleanSettings,
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
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

    case 'change_password':
        $input = json_decode(file_get_contents('php://input'), true);
        $oldPass = $input['old_password'] ?? '';
        $newPass = $input['new_password'] ?? '';

        if (password_verify($oldPass, $settings['admin_password_hash'])) {
            if (strlen($newPass) < 6) {
                echo json_encode(['success' => false, 'message' => 'Новый пароль слишком короткий (мин. 6 символов)']);
            } else {
                $settings['admin_password_hash'] = password_hash($newPass, PASSWORD_DEFAULT);
                $settingsStorage->save($settings);
                echo json_encode(['success' => true]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Неверный текущий пароль']);
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

    case 'update_discount':
        $id = $_GET['id'] ?? '';
        $discount = (int)($_GET['discount'] ?? 0);
        $regs = $regStorage->getAll();
        foreach ($regs as &$reg) {
            if ($reg['id'] === $id) {
                $reg['discount'] = $discount;
            }
        }
        $regStorage->save($regs);
        echo json_encode(['success' => true]);
        break;

    case 'bulk_action':
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];
        $type = $input['type'] ?? '';
        $discount = (int)($input['discount'] ?? 0);

        if (empty($ids)) {
            echo json_encode(['success' => false, 'message' => 'No IDs provided']);
            exit;
        }

        $regs = $regStorage->getAll();

        if ($type === 'delete') {
            $regs = array_values(array_filter($regs, function($r) use ($ids) {
                return !in_array($r['id'], $ids);
            }));
        } elseif ($type === 'approve') {
            foreach ($regs as &$reg) {
                if (in_array($reg['id'], $ids)) {
                    $reg['status'] = 'approved';
                    $reg['discount'] = $discount;
                }
            }
        }

        $regStorage->save($regs);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
