<?php
require_once '../includes/Auth.php';
require_once '../includes/Storage.php';

header('Content-Type: application/json');

if (!Auth::isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    echo json_encode(Storage::read('products'));
} elseif ($method === 'POST') {
    $products = Storage::read('products');
    $data = $_POST;

    $id = !empty($data['id']) ? $data['id'] : uniqid();
    $image = $data['existing_image'] ?? '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['image']['tmp_name']);
            if (strpos($mime, 'image/') === 0) {
                $filename = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $filename);
                $image = 'uploads/' . $filename;
            }
        }
    }

    $product = [
        'id' => $id,
        'name' => $data['name'],
        'description' => $data['description'],
        'price' => (float)$data['price'],
        'stock' => (int)$data['stock'],
        'sufficient' => ($data['sufficient'] ?? 'false') === 'true',
        'image' => $image
    ];

    $found = false;
    foreach ($products as &$p) {
        if ($p['id'] === $id) {
            $p = $product;
            $found = true;
            break;
        }
    }
    if (!$found) $products[] = $product;

    Storage::write('products', $products);
    echo json_encode(['success' => true]);
} elseif ($method === 'DELETE') {
    $id = $_GET['id'];
    $products = Storage::read('products');
    $products = array_values(array_filter($products, fn($p) => $p['id'] !== $id));
    Storage::write('products', $products);
    echo json_encode(['success' => true]);
}
