<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\ProductController;
use App\Controllers\TemplateController;
use App\Services\BarcodeService;
use App\Services\PDFService;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);

// Simple token verification
function checkAuth() {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (strpos($auth, 'Bearer vsprint_token_') !== 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

try {
    if ($path === '/auth/login' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode((new AuthController())->login($data));
        exit;
    }

    if ($path === '/barcode' && $method === 'GET') {
        $text = $_GET['text'] ?? '12345678';
        $type = $_GET['type'] ?? 'CODE128';
        header('Content-Type: image/svg+xml');
        echo (new BarcodeService())->generate($text, $type);
        exit;
    }

    // Protected Routes
    checkAuth();

    if ($path === '/print' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $pdf = (new PDFService())->generateLabel($data['template'], $data['product'] ?? []);
        header('Content-Type: application/pdf');
        echo $pdf;
        exit;
    }

    if ($path === '/products' && $method === 'GET') {
        echo json_encode((new ProductController())->index());
    } elseif ($path === '/products' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode((new ProductController())->store($data));
    } elseif ($path === '/templates' && $method === 'GET') {
        echo json_encode((new TemplateController())->index());
    } elseif ($path === '/templates' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode((new TemplateController())->store($data));
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
