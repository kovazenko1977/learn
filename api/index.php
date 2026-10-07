<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/Storage.php';

$storage = new Storage();

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($action) {
        case 'get_points':
            $points = $storage->getPoints();
            $visits = $storage->getVisits();
            $settings = $storage->getSettings();
            echo json_encode([
                'success' => true,
                'points' => $points,
                'visits' => $visits,
                'settings' => $settings
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'save_point':
            $id = $input['id'] ?? '';
            $name = trim($input['name'] ?? '');
            $address = trim($input['address'] ?? '');
            $sim_number = trim($input['sim_number'] ?? '');
            $equipment_type = trim($input['equipment_type'] ?? '');
            $contact_person = trim($input['contact_person'] ?? '');
            $contact_phone = trim($input['contact_phone'] ?? '');
            $notes = trim($input['notes'] ?? '');

            if (empty($name) || empty($address) || empty($sim_number)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Наименование, адрес и номер SIM-карты обязательны для заполнения.']);
                exit;
            }

            $pointData = [
                'name' => $name,
                'address' => $address,
                'sim_number' => $sim_number,
                'equipment_type' => $equipment_type,
                'contact_person' => $contact_person,
                'contact_phone' => $contact_phone,
                'notes' => $notes
            ];

            if ($id) {
                $saved = $storage->updatePoint($id, $pointData);
            } else {
                $saved = $storage->addPoint($pointData);
            }

            echo json_encode(['success' => true, 'point' => $saved], JSON_UNESCAPED_UNICODE);
            break;

        case 'delete_point':
            $id = $input['id'] ?? $_GET['id'] ?? '';
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Не указан ID точки']);
                exit;
            }

            $success = $storage->deletePoint($id);
            echo json_encode(['success' => $success], JSON_UNESCAPED_UNICODE);
            break;

        case 'cancel_visit':
            $point_id = $input['point_id'] ?? $_GET['point_id'] ?? '';
            if (!$point_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Не указан ID точки']);
                exit;
            }

            $success = $storage->cancelLatestVisit($point_id);
            echo json_encode([
                'success' => $success,
                'points' => $storage->getPoints(),
                'visits' => $storage->getVisits()
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'record_visit':
            $point_id = $input['point_id'] ?? '';
            $point = $storage->getPoint($point_id);

            if (!$point) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Точка не найдена']);
                exit;
            }

            $visitRecord = [
                'point_id' => $point['id'],
                'sim_number' => $point['sim_number'],
                'point_name' => $point['name'],
                'visited_at' => date('Y-m-d H:i:s'),
                'has_defects' => !empty($input['has_defects']),
                'defects_description' => trim($input['defects_description'] ?? ''),
                'notes' => trim($input['notes'] ?? ''),
                'technician' => $input['technician'] ?? $storage->getSettings()['technician_name'] ?? 'Инженер ТО'
            ];

            $savedVisit = $storage->addVisit($visitRecord);
            echo json_encode([
                'success' => true,
                'visit' => $savedVisit,
                'points' => $storage->getPoints(),
                'visits' => $storage->getVisits()
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'get_settings':
            echo json_encode(['success' => true, 'settings' => $storage->getSettings()], JSON_UNESCAPED_UNICODE);
            break;

        case 'save_settings':
            $interval_days = isset($input['interval_days']) ? max(1, intval($input['interval_days'])) : 30;
            $interval_mode = in_array($input['interval_mode'] ?? '', ['days', 'calendar_month']) ? $input['interval_mode'] : 'days';
            $technician_name = trim($input['technician_name'] ?? '');
            $company_name = trim($input['company_name'] ?? '');

            $settingsData = [
                'interval_days' => $interval_days,
                'interval_mode' => $interval_mode,
                'technician_name' => $technician_name,
                'company_name' => $company_name
            ];

            $storage->saveSettings($settingsData);
            echo json_encode(['success' => true, 'settings' => $storage->getSettings()], JSON_UNESCAPED_UNICODE);
            break;

        case 'reset_intervals':
            $storage->clearAllVisits();
            echo json_encode([
                'success' => true,
                'points' => $storage->getPoints(),
                'visits' => $storage->getVisits(),
                'settings' => $storage->getSettings()
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'reset_demo':
            unlink(__DIR__ . '/../data/points.json');
            unlink(__DIR__ . '/../data/visits.json');
            unlink(__DIR__ . '/../data/settings.json');
            $storage->seedDefaultDataIfNeeded();
            echo json_encode([
                'success' => true,
                'points' => $storage->getPoints(),
                'visits' => $storage->getVisits(),
                'settings' => $storage->getSettings()
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
