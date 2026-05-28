<?php
require_once __DIR__ . '/../vendor/autoload.php';

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

switch ($action) {
    case 'products':
        $ctrl = new App\Controllers\ProductController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->store();
        } else {
            $ctrl->index();
        }
        break;

    case 'product':
        $ctrl = new App\Controllers\ProductController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->update($id);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $ctrl->destroy($id);
        } else {
            $ctrl->show($id);
        }
        break;

    case 'categories':
        $ctrl = new App\Controllers\ProductController();
        $ctrl->categories();
        break;

    case 'templates':
        $ctrl = new App\Controllers\TemplateController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->store();
        } else {
            $ctrl->index();
        }
        break;

    case 'template':
        $ctrl = new App\Controllers\TemplateController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->update($id);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $ctrl->destroy($id);
        } else {
            $ctrl->show($id);
        }
        break;

    case 'print':
        $ctrl = new App\Controllers\TemplateController();
        $ctrl->print();
        break;

    case 'barcode':
        $ctrl = new App\Controllers\TemplateController();
        $ctrl->previewBarcode();
        break;

    case 'import':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
            $importer = new App\Services\ImportService();
            $catId = $_POST['category_id'] ?? null;
            $count = $importer->importFromExcel($_FILES['file']['tmp_name'], $catId);
            header('Content-Type: application/json');
            echo json_encode(['message' => "Imported $count products"]);
        }
        break;

    default:
        header('Content-Type: application/json');
        echo json_encode(['message' => 'LabelPro API v1.0']);
        break;
}
