<?php
require_once __DIR__ . '/../includes/Storage.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$settings = Storage::read('settings.json');
$knowledge = Storage::read('knowledge.json');

$input = json_decode(file_get_contents('php://input'), true);
$rawMessage = isset($input['message']) ? trim($input['message']) : '';
$message = mb_strtolower($rawMessage);

if (empty($rawMessage)) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

// 1. Check working hours (Flexible Schedule)
date_default_timezone_set($settings['working_hours']['timezone'] ?? 'Europe/Moscow');
$dayOfWeek = date('w'); // 0 (Sun) to 6 (Sat)
$now = date('H:i');

$daySchedule = $settings['schedule'][$dayOfWeek] ?? null;

if ($daySchedule) {
    $isOpen = $daySchedule['enabled'];
    if ($isOpen) {
        if ($now < $daySchedule['start'] || $now > $daySchedule['end']) {
            $isOpen = false;
        }
    }

    if (!$isOpen) {
        echo json_encode([
            'answer' => $settings['working_hours']['out_of_hours_message'],
            'is_fallback' => false,
            'show_lead_form' => true
        ]);
        exit;
    }
}

// 2. Multibyte Similarity helper
function mb_similarity($str1, $str2) {
    $len1 = mb_strlen($str1);
    $len2 = mb_strlen($str2);
    if ($len1 === 0 || $len2 === 0) return 0;

    // Simple word overlap for Russian
    $words1 = preg_split('/\s+/u', $str1);
    $words2 = preg_split('/\s+/u', $str2);

    $intersection = array_intersect($words1, $words2);
    $overlap = count($intersection) / max(count($words1), count($words2)) * 100;

    // Character based similarity as fallback/refinement
    $chars1 = preg_split('//u', $str1, -1, PREG_SPLIT_NO_EMPTY);
    $chars2 = preg_split('//u', $str2, -1, PREG_SPLIT_NO_EMPTY);
    $char_intersection = array_intersect($chars1, $chars2);
    $char_overlap = count($char_intersection) / max(count($chars1), count($chars2)) * 100;

    return ($overlap * 0.7) + ($char_overlap * 0.3);
}

// 3. Search for answer
$bestMatch = null;
$highestScore = 0;

foreach ($knowledge as $item) {
    foreach ($item['keywords'] as $keyword) {
        $keyword = mb_strtolower($keyword);

        // Exact substring match (highest priority)
        if (mb_strpos($message, $keyword) !== false) {
            $score = 90 + (mb_strlen($keyword) / mb_strlen($message) * 10);
            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $item['answer'];
            }
        }

        // Fuzzy match
        $sim = mb_similarity($message, $keyword);
        if ($sim > $highestScore) {
            $highestScore = $sim;
            $bestMatch = $item['answer'];
        }
    }
}

$threshold = $settings['fallback']['threshold'] ?? 40;
$response = [];

if ($highestScore >= $threshold && $bestMatch) {
    // Check for form triggers: [form:ID]
    $formId = null;
    if (preg_match('/\[form:([a-zA-Z0-9_-]+)\]/', $bestMatch, $matches)) {
        $formId = $matches[1];
        $bestMatch = str_replace($matches[0], '', $bestMatch);
    }

    $response = [
        'answer' => trim($bestMatch),
        'score' => $highestScore,
        'is_fallback' => false,
        'form_id' => $formId
    ];
} else {
    $response = [
        'answer' => $settings['fallback']['message'],
        'score' => $highestScore,
        'is_fallback' => true,
        'button_text' => $settings['fallback']['button_text'],
        'phone' => $settings['contacts']['phone'],
        'show_lead_form' => true
    ];
}

// 4. Handle notifications for form submissions
if (strpos($rawMessage, 'FORM_SUBMISSION') === 0 || strpos($rawMessage, 'LEAD_PHONE') === 0) {
    $notif = $settings['notifications'] ?? [];
    $subject = "Новая заявка из чат-бота";
    $body = $rawMessage;

    // Email
    if (($notif['email']['enabled'] ?? false) && !empty($notif['email']['address'])) {
        @mail($notif['email']['address'], $subject, $body);
    }

    // Telegram
    if (($notif['telegram']['enabled'] ?? false) && !empty($notif['telegram']['token']) && !empty($notif['telegram']['chat_id'])) {
        $token = $notif['telegram']['token'];
        $chat_id = $notif['telegram']['chat_id'];
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $text = "🔔 {$subject}\n\n" . str_replace(['FORM_SUBMISSION', 'LEAD_PHONE'], '', $body);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'chat_id' => $chat_id,
            'text' => $text
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        curl_close($ch);
    }
}

// Log history
$history = Storage::read('history.json') ?: [];
$history[] = [
    'id' => uniqid(),
    'timestamp' => date('Y-m-d H:i:s'),
    'user_message' => $message,
    'bot_answer' => $response['answer'],
    'score' => $highestScore,
    'is_fallback' => $response['is_fallback']
];
Storage::write('history.json', array_slice($history, -1000)); // Keep last 1000 messages

echo json_encode($response);
