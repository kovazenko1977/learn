<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

Auth::requireRole(['superadmin', 'admin_content', 'client']);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $docs = Storage::read('documents');
        $user = Auth::getCurrentUser();

        if ($user['role'] === 'client') {
            $docs = array_filter($docs, function($doc) use ($user) {
                return $doc['is_public'] || $doc['client_id'] === $user['id'];
            });
        }
        echo json_encode(['success' => true, 'documents' => array_values($docs)]);
        break;

    case 'upload':
        Auth::requireRole(['superadmin', 'admin_content']);
        if (!isset($_FILES['file'])) {
            echo json_encode(['success' => false, 'error' => 'No file uploaded']);
            break;
        }

        $tmp_name = $_FILES['file']['tmp_name'];
        $original_name = Security::sanitize($_FILES['file']['name']); // Sanitize name
        $id = uniqid();
        $dest_path = __DIR__ . '/../uploads/' . $id . '.enc';

        if (Security::encryptFile($tmp_name, $dest_path)) {
            $doc = [
                'id' => $id,
                'name' => Security::sanitize($_POST['name'] ?? $original_name), // Sanitize input
                'original_name' => $original_name,
                'client_id' => Security::sanitize($_POST['client_id'] ?? null), // Sanitize ID
                'is_public' => ($_POST['is_public'] ?? 'false') === 'true',
                'meta' => [
                    'type' => Security::sanitize($_POST['type'] ?? ''),
                    'category' => Security::sanitize($_POST['category'] ?? ''),
                    'expiry_date' => Security::sanitize($_POST['expiry_date'] ?? null)
                ],
                'uploaded_at' => date('Y-m-d H:i:s')
            ];
            Storage::insert('documents', $doc);
            Security::log('upload_document', $_SESSION['user_id'], 'documents', ['id' => $id, 'name' => $doc['name']]);
            echo json_encode(['success' => true, 'id' => $id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Encryption failed']);
        }
        break;

    case 'download':
        $id = $_GET['id'] ?? '';
        $doc = Storage::findOne('documents', ['id' => $id]);
        if (!$doc) {
            http_response_code(404);
            echo json_encode(['error' => 'Document not found']);
            break;
        }

        $user = Auth::getCurrentUser();
        if ($user['role'] === 'client' && !$doc['is_public'] && $doc['client_id'] !== $user['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            break;
        }

        $path = __DIR__ . '/../uploads/' . $id . '.enc';
        if (!file_exists($path)) {
            http_response_code(404);
            echo json_encode(['error' => 'File missing']);
            break;
        }

        Security::log('download_document', $user['id'], 'documents', ['id' => $id, 'name' => $doc['name']]);
        Security::decryptFile($path, $doc['original_name']);
        exit;

    case 'delete':
        Auth::requireRole(['superadmin', 'admin_content']);
        $id = $_GET['id'] ?? '';
        $doc = Storage::findOne('documents', ['id' => $id]);
        if ($doc) {
            $path = __DIR__ . '/../uploads/' . $id . '.enc';
            if (file_exists($path)) unlink($path);
            Storage::delete('documents', $id);
            Security::log('delete_document', $_SESSION['user_id'], 'documents', ['id' => $id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Document not found']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
