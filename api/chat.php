<?php
Auth::requireRole(['admin', 'senior_admin', 'manager', 'doctor', 'patient']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $dialog_id = isset($_GET['dialog_id']) ? $_GET['dialog_id'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    $messages = Storage::list('chat');

    if ($dialog_id) {
        $messages = array_filter($messages, fn($m) => $m['dialog_id'] === $dialog_id);
    }

    if ($search) {
        $messages = array_filter($messages, fn($m) => stripos($m['text'], $search) !== false);
    }

    echo json_encode(array_values($messages));
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $input = Security::sanitize($input);

    $id = uniqid();
    $input['id'] = $id;
    $input['from_user_id'] = $_SESSION['user_id'];
    $input['created_at'] = date('c');
    $input['read_by'] = [$_SESSION['user_id']];

    Storage::write('chat', $id, $input);
    echo json_encode(['success' => true, 'id' => $id]);
}
