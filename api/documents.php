<?php
Auth::requireRole(['admin', 'doctor', 'patient']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : null;
    $docs = Storage::list('documents');
    if ($patient_id) {
        $docs = array_filter($docs, fn($d) => $d['patient_id'] === $patient_id);
        $docs = array_values($docs);
    }
    echo json_encode($docs);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $input = Security::sanitize($input);

    $id = isset($input['id']) ? $input['id'] : uniqid();
    $existing = Storage::read('documents', $id);

    if ($existing) {
        Storage::saveVersion('documents', $id, $existing);
    }

    $input['id'] = $id;
    if (!$existing) {
        $input['created_at'] = date('c');
        $input['created_by'] = $_SESSION['user_id'];
    }

    // Placeholder for PDF generation
    if (isset($input['patient_id'])) {
        $input['file_path'] = "/storage/files/patients/{$input['patient_id']}/{$id}.pdf";
    }

    Storage::write('documents', $id, $input);
    echo json_encode(['success' => true, 'id' => $id]);
}
