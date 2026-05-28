<?php
namespace App\Controllers;
use App\Models\Template;
use App\Services\PdfService;
use App\Services\BarcodeService;
class TemplateController extends BaseController {
    private Template $templateModel;
    public function __construct() {
        $this->templateModel = new Template();
    }
    public function index() {
        $templates = $this->templateModel->getAll();
        $this->json($templates);
    }
    public function show($id) {
        $template = $this->templateModel->getById($id);
        if (!$template) {
            $this->json(['error' => 'Template not found'], 404);
        }
        $this->json($template);
    }
    public function store() {
        $data = $this->getRequestData();
        $id = $this->templateModel->create($data);
        $this->json(['id' => $id, 'message' => 'Template saved']);
    }
    public function update($id) {
        $data = $this->getRequestData();
        $this->templateModel->update($id, $data);
        $this->json(['message' => 'Template updated']);
    }
    public function destroy($id) {
        $this->templateModel->delete($id);
        $this->json(['message' => 'Template deleted']);
    }
    public function print() {
        $data = $this->getRequestData();
        $elements = $data['elements'] ?? [];
        $width = $data['width'] ?? 58;
        $height = $data['height'] ?? 40;
        $pdfService = new PdfService();
        $pdfContent = $pdfService->generateLabel($width, $height, $elements);
        header('Content-Type: application/pdf');
        echo $pdfContent;
        exit;
    }
    public function previewBarcode() {
        $code = $_GET['code'] ?? '12345678';
        $type = $_GET['type'] ?? 'CODE128';
        $barcodeService = new BarcodeService();
        $svg = $barcodeService->generateSVG($code, $type);
        header('Content-Type: image/svg+xml');
        echo $svg;
        exit;
    }
}
