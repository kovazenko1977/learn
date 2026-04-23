<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Некорректные данные']);
    exit;
}

$settingsStorage = new Storage('settings.json');
$settings = $settingsStorage->getAll();

// Server-side validation
foreach ($settings['form_fields'] as $field) {
    if (($field['required'] ?? false) && empty($input[$field['id']])) {
        echo json_encode(['success' => false, 'message' => "Поле '{$field['label']}' обязательно для заполнения"]);
        exit;
    }
}

$storage = new Storage('registrations.json');

// Initialize with pending status and 0 discount
$input['status'] = 'pending';
$input['discount'] = 0;

$items = $storage->getAll();
$id = time() . '_' . uniqid();
$input['id'] = $id;
$input['created_at'] = date('Y-m-d H:i:s');
$items[] = $input;
$result = $storage->save($items);

if ($result) {
    // Send notifications
    if (!empty($settings['social']['telegram_token']) && !empty($settings['social']['telegram_chat_id'])) {
        $message = "🎉 *Новая регистрация!*\n\n";

        // Map field IDs to labels
        foreach ($settings['form_fields'] as $field) {
            $val = $input[$field['id']] ?? '';
            if ($val !== '') {
                $message .= "• *{$field['label']}:* {$val}\n";
            }
        }

        $url = "https://api.telegram.org/bot{$settings['social']['telegram_token']}/sendMessage";
        $data = [
            'chat_id' => $settings['social']['telegram_chat_id'],
            'text' => $message,
            'parse_mode' => 'Markdown'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_exec($ch);
        curl_close($ch);
    }

    echo json_encode(['success' => true, 'id' => $id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка сохранения в базу']);
}
