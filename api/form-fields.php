<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$storage = Storage::getInstance();
$currentUser = Auth::validateToken();
$method = $_SERVER['REQUEST_METHOD'];

// GET form fields is accessible by any authenticated user
if ($method === 'GET') {
    $fields = $storage->get('form_fields');
    usort($fields, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
    echo json_encode(['success' => true, 'fields' => $fields]);
    exit;
}

// Admin only for modifying form fields schema
if (!$currentUser || $currentUser['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Только администратор может изменять конструктор форм']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if ($method === 'POST') {
    $action = $_GET['action'] ?? '';

    // Reorder form fields via drag-and-drop
    if ($action === 'reorder') {
        $orderedIds = $input['order'] ?? [];
        foreach ($orderedIds as $index => $fieldId) {
            $storage->update('form_fields', $fieldId, ['order' => $index + 1]);
        }
        $fields = $storage->get('form_fields');
        usort($fields, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        echo json_encode(['success' => true, 'fields' => $fields]);
        exit;
    }

    $id = $input['id'] ?? null;
    $label = trim($input['label'] ?? '');
    $fieldType = trim($input['field_type'] ?? 'text');
    $required = (bool)($input['required'] ?? false);
    $options = is_array($input['options'] ?? null) ? $input['options'] : [];
    $placeholder = trim($input['placeholder'] ?? '');

    if (!$label) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Укажите название (label) поля']);
        exit;
    }

    if ($id) {
        $storage->update('form_fields', $id, [
            'label' => $label,
            'field_type' => $fieldType,
            'required' => $required,
            'options' => $options,
            'placeholder' => $placeholder
        ]);
    } else {
        $fieldName = 'field_' . preg_replace('/[^a-z0-9_]/', '', strtolower(bin2hex(random_bytes(4))));
        $allFields = $storage->get('form_fields');
        $maxOrder = count($allFields);

        $storage->insert('form_fields', [
            'label' => $label,
            'field_name' => $fieldName,
            'field_type' => $fieldType,
            'required' => $required,
            'options' => $options,
            'placeholder' => $placeholder,
            'order' => $maxOrder + 1
        ]);
    }

    $fields = $storage->get('form_fields');
    usort($fields, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

    echo json_encode(['success' => true, 'message' => 'Конструктор форм обновлён', 'fields' => $fields]);
    exit;
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Укажите ID поля']);
        exit;
    }

    $storage->delete('form_fields', $id);
    $fields = $storage->get('form_fields');
    usort($fields, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

    echo json_encode(['success' => true, 'message' => 'Поле удалено', 'fields' => $fields]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
