<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

Auth::requireRole(['superadmin', 'admin_content', 'client']);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        $data = Storage::read('pricelist');
        if (empty($data)) {
            $data = ['items' => [], 'config' => ['columns' => ['code', 'name', 'unit', 'price']]];
        }
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'save_item':
        Auth::requireRole(['superadmin', 'admin_content']);
        $item = json_decode(file_get_contents('php://input'), true);
        $item = Security::sanitize($item);

        $data = Storage::read('pricelist');
        if (empty($data)) $data = ['items' => [], 'config' => ['columns' => ['code', 'name', 'unit', 'price']]];

        if (!isset($item['id']) || empty($item['id'])) {
            $item['id'] = uniqid();
            $data['items'][] = $item;
            Security::log('pricelist_add', $_SESSION['user_id'], 'pricelist', ['id' => $item['id']]);
        } else {
            foreach ($data['items'] as &$existing) {
                if ($existing['id'] === $item['id']) {
                    $existing = array_merge($existing, $item);
                    break;
                }
            }
            Security::log('pricelist_update', $_SESSION['user_id'], 'pricelist', ['id' => $item['id']]);
        }

        Storage::write('pricelist', $data);
        echo json_encode(['success' => true]);
        break;

    case 'delete_item':
        Auth::requireRole(['superadmin', 'admin_content']);
        $id = $_GET['id'] ?? '';
        $data = Storage::read('pricelist');
        $data['items'] = array_filter($data['items'], fn($i) => $i['id'] !== $id);
        Storage::write('pricelist', $data);
        Security::log('pricelist_delete', $_SESSION['user_id'], 'pricelist', ['id' => $id]);
        echo json_encode(['success' => true]);
        break;

    case 'update_config':
        Auth::requireRole(['superadmin', 'admin_content']);
        $config = json_decode(file_get_contents('php://input'), true);
        $data = Storage::read('pricelist');
        $data['config'] = Security::sanitize($config);
        Storage::write('pricelist', $data);
        Security::log('pricelist_config', $_SESSION['user_id'], 'pricelist');
        echo json_encode(['success' => true]);
        break;

    case 'export_csv':
        $data = Storage::read('pricelist');
        $items = $data['items'] ?? [];
        $cols = $data['config']['columns'] ?? ['code', 'name', 'unit', 'price'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="pricelist_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

        $headers = [];
        if (in_array('code', $cols)) $headers[] = 'Код';
        if (in_array('name', $cols)) $headers[] = 'Наименование';
        if (in_array('unit', $cols)) $headers[] = 'Ед.изм.';
        if (in_array('price', $cols)) $headers[] = 'Цена';

        fputcsv($output, $headers, ';');

        foreach ($items as $item) {
            $row = [];
            if (in_array('code', $cols)) $row[] = $item['code'] ?? '';
            if (in_array('name', $cols)) $row[] = $item['name'] ?? '';
            if (in_array('unit', $cols)) $row[] = $item['unit'] ?? '';
            if (in_array('price', $cols)) $row[] = $item['price'] ?? '';
            fputcsv($output, $row, ';');
        }
        fclose($output);
        exit;

    case 'import':
        // Placeholder for future CSV/Excel import
        Auth::requireRole(['superadmin', 'admin_content']);
        echo json_encode(['success' => false, 'error' => 'Import not implemented yet']);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
