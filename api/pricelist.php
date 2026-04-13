<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

Auth::requireRole(['superadmin', 'admin_content', 'client']);

$action = $_GET['action'] ?? '';
$list_id = $_GET['list_id'] ?? '';

// Helper to migrate old structure if exists
function get_all_lists() {
    $data = Storage::read('pricelist');
    if (empty($data)) return [];

    // Check if it is the old structure
    if (isset($data['items']) && !isset($data[0])) {
        // Migrate
        $newList = [
            'id' => 'default',
            'name' => 'Основной прайс',
            'columns' => [
                ['id' => 'code', 'label' => 'Код', 'type' => 'text'],
                ['id' => 'name', 'label' => 'Наименование', 'type' => 'text'],
                ['id' => 'unit', 'label' => 'Ед.изм.', 'type' => 'text'],
                ['id' => 'price', 'label' => 'Цена', 'type' => 'number']
            ],
            'items' => $data['items']
        ];
        $data = [$newList];
        Storage::write('pricelist', $data);
    }
    return $data;
}

switch ($action) {
    case 'list':
        $lists = get_all_lists();
        $summary = array_map(fn($l) => ['id' => $l['id'], 'name' => $l['name']], $lists);
        echo json_encode(['success' => true, 'lists' => $summary]);
        break;

    case 'get':
        $lists = get_all_lists();
        $user = Auth::getCurrentUser();

        // If client, force their assigned list
        if ($user['role'] === 'client') {
            $list_id = $user['assigned_pricelist_id'] ?? 'default';
        }

        if (!$list_id) {
            $list_id = 'default';
        }

        $list = null;
        foreach ($lists as $l) {
            if ($l['id'] === $list_id) {
                $list = $l;
                break;
            }
        }

        if (!$list && $list_id === 'default' && empty($lists)) {
             // Return empty default
             $list = [
                'id' => 'default',
                'name' => 'Основной прайс',
                'columns' => [
                    ['id' => 'code', 'label' => 'Код', 'type' => 'text'],
                    ['id' => 'name', 'label' => 'Наименование', 'type' => 'text'],
                    ['id' => 'unit', 'label' => 'Ед.изм.', 'type' => 'text'],
                    ['id' => 'price', 'label' => 'Цена', 'type' => 'number']
                ],
                'items' => []
            ];
        }

        if ($list) {
            echo json_encode(['success' => true, 'data' => $list]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Price list not found']);
        }
        break;

    case 'save_list':
        Auth::requireRole(['superadmin', 'admin_content']);
        $input = json_decode(file_get_contents('php://input'), true);
        $input = Security::sanitize($input);

        $lists = get_all_lists();
        if (empty($input['id'])) {
            $input['id'] = uniqid('list_');
            $input['items'] = [];
            $lists[] = $input;
            Security::log('pricelist_create', $_SESSION['user_id'], 'pricelist', ['id' => $input['id']]);
        } else {
            foreach ($lists as &$l) {
                if ($l['id'] === $input['id']) {
                    $l['name'] = $input['name'];
                    $l['columns'] = $input['columns'];
                    break;
                }
            }
            Security::log('pricelist_update_meta', $_SESSION['user_id'], 'pricelist', ['id' => $input['id']]);
        }
        Storage::write('pricelist', $lists);
        echo json_encode(['success' => true, 'id' => $input['id']]);
        break;

    case 'delete_list':
        Auth::requireRole(['superadmin', 'admin_content']);
        $id = $_GET['id'] ?? '';
        $lists = get_all_lists();
        $lists = array_filter($lists, fn($l) => $l['id'] !== $id);
        $input['updated_at'] = date('Y-m-d H:i:s');
        Storage::write('pricelist', array_values($lists));
        Security::log('pricelist_delete_list', $_SESSION['user_id'], 'pricelist', ['id' => $id]);
        echo json_encode(['success' => true]);
        break;

    case 'save_item':
        Auth::requireRole(['superadmin', 'admin_content']);
        $item = json_decode(file_get_contents('php://input'), true);
        $item = Security::sanitize($item);
        $target_list_id = $_GET['list_id'] ?? '';

        $lists = get_all_lists();
        $found = false;
        foreach ($lists as &$l) {
            if ($l['id'] === $target_list_id) {
                if (empty($item['id'])) {
                    $item['id'] = uniqid('item_');
                    $l['items'][] = $item;
                } else {
                    foreach ($l['items'] as &$existing) {
                        if ($existing['id'] === $item['id']) {
                            $existing = array_merge($existing, $item);
                            break;
                        }
                    }
                }
                $found = true;
                break;
            }
        }

        if ($found) {
            Storage::write('pricelist', $lists);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'List not found']);
        }
        break;

    case 'delete_item':
        Auth::requireRole(['superadmin', 'admin_content']);
        $item_id = $_GET['id'] ?? '';
        $target_list_id = $_GET['list_id'] ?? '';

        $lists = get_all_lists();
        foreach ($lists as &$l) {
            if ($l['id'] === $target_list_id) {
                $l['items'] = array_filter($l['items'], fn($i) => $i['id'] !== $item_id);
                $l['items'] = array_values($l['items']);
                break;
            }
        }
        Storage::write('pricelist', $lists);
        echo json_encode(['success' => true]);
        break;

    case 'import_csv':
        Auth::requireRole(['superadmin', 'admin_content']);
        if (!isset($_FILES['file'])) {
            echo json_encode(['success' => false, 'error' => 'No file uploaded']);
            break;
        }

        $target_list_id = $_GET['list_id'] ?? '';
        $lists = get_all_lists();
        $target_list = null;
        $idx = -1;
        foreach ($lists as $i => $l) {
            if ($l['id'] === $target_list_id) {
                $target_list = $l;
                $idx = $i;
                break;
            }
        }

        if (!$target_list) {
            echo json_encode(['success' => false, 'error' => 'Target list not found']);
            break;
        }

        $clear_existing = ($_POST['clear'] ?? 'false') === 'true';

        $handle = fopen($_FILES['file']['tmp_name'], "r");

        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            rewind($handle);
        }

        // Auto-detect delimiter
        $firstLine = fgets($handle);
        rewind($handle);
        if ($bom === chr(0xEF).chr(0xBB).chr(0xBF)) fread($handle, 3);

        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';

        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            echo json_encode(['success' => false, 'error' => 'Invalid CSV format']);
            break;
        }

        $items = [];
        $cols = $target_list['columns'];

        // Map header labels to column IDs
        $col_map = [];
        foreach ($header as $i => $label) {
            $label = trim($label);
            foreach ($cols as $c) {
                if ($c['label'] === $label) {
                    $col_map[$i] = $c['id'];
                    break;
                }
            }
        }

        // If mapping by label failed, map by order
        if (empty($col_map)) {
            foreach ($cols as $i => $c) {
                if (isset($header[$i])) {
                    $col_map[$i] = $c['id'];
                }
            }
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            $item = ['id' => uniqid('item_')];
            foreach ($row as $i => $val) {
                if (isset($col_map[$i])) {
                    $val = Security::sanitize($val);
                    if ($cols[array_search($col_map[$i], array_column($cols, 'id'))]['type'] === 'number') {
                        $val = (float)str_replace(',', '.', preg_replace('/[^0-9,.]/', '', $val));
                    }
                    $item[$col_map[$i]] = $val;
                }
            }
            // Fill missing columns with empty strings
            foreach ($cols as $c) {
                if (!isset($item[$c['id']])) {
                    $item[$c['id']] = $c['type'] === 'number' ? 0 : '';
                }
            }
            $items[] = $item;
        }
        fclose($handle);

        if ($clear_existing) {
            $lists[$idx]['items'] = $items;
        } else {
            $lists[$idx]['items'] = array_merge($lists[$idx]['items'], $items);
        }
        $lists[$idx]['updated_at'] = date('Y-m-d H:i:s');
        Storage::write('pricelist', $lists);
        Security::log('pricelist_import', $_SESSION['user_id'], 'pricelist', ['id' => $target_list_id, 'count' => count($items)]);

        echo json_encode(['success' => true, 'count' => count($items)]);
        break;

    case 'export_csv':
        $lists = get_all_lists();
        $list = null;
        foreach ($lists as $l) {
            if ($l['id'] === $list_id) {
                $list = $l;
                break;
            }
        }
        if (!$list) exit;

        $items = $list['items'] ?? [];
        $cols = $list['columns'] ?? [];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="pricelist_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

        $headers = array_map(fn($c) => $c['label'], $cols);
        fputcsv($output, $headers, ';');

        foreach ($items as $item) {
            $row = [];
            foreach ($cols as $c) {
                $row[] = $item[$c['id']] ?? '';
            }
            fputcsv($output, $row, ';');
        }
        fclose($output);
        exit;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
