<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Database;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Simple Authorization Check (Simplified for MVP)
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$is_authenticated = (strpos($auth_header, 'Bearer ') === 0);

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);

// Simple Router
try {
    // Public routes
    if ($path === '/auth/login' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $controller = new \App\Controllers\AuthController();
        echo json_encode($controller->login($data));
        exit;
    }

    if ($path === '/barcode' && $method === 'GET') {
        $text = $_GET['text'] ?? '12345678';
        $type = $_GET['type'] ?? 'CODE128';
        $service = new \App\Services\BarcodeService();
        header('Content-Type: image/svg+xml');
        echo $service->generate($text, $type);
        exit;
    }

    // Protected routes (simple check for demo/MVP)
    // In real app, verify JWT here

    if ($path === '/print' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $template = $data['template'];
        $product = $data['product'] ?? ['name' => 'Демо продукт'];
        $service = new \App\Services\PDFService();
        $pdf = $service->generateLabel($template, $product);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="label.pdf"');
        echo $pdf;
        exit;
    }

    if ($path === '/products' && $method === 'GET') {
        $controller = new \App\Controllers\ProductController();
        echo json_encode($controller->index());
    } elseif ($path === '/products' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $controller = new \App\Controllers\ProductController();
        echo json_encode($controller->store($data));
    } elseif ($path === '/templates' && $method === 'GET') {
        $controller = new \App\Controllers\TemplateController();
        echo json_encode($controller->index());
    } elseif ($path === '/templates' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $controller = new \App\Controllers\TemplateController();
        echo json_encode($controller->store($data));
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Not Found', 'path' => $path]);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
