<?php
Auth::requireRole(['admin', 'director']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $transactions = Storage::list('finance');
    echo json_encode($transactions);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $input = Security::sanitize($input);
    $id = uniqid();
    $input['id'] = $id;
    $input['created_at'] = date('c');
    $input['created_by'] = $_SESSION['user_id'];
    Storage::write('finance', $id, $input);
    echo json_encode(['success' => true, 'id' => $id]);
}
