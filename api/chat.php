<?php
require_once __DIR__ . '/../includes/Storage.php';

header('Content-Type: application/json');

$settings = Storage::read('settings.json');
$knowledge = Storage::read('knowledge.json');

$input = json_decode(file_get_contents('php://input'), true);
$message = isset($input['message']) ? mb_strtolower(trim($input['message'])) : '';

if (empty($message)) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

// 1. Check working hours
if ($settings['working_hours']['enabled']) {
    date_default_timezone_set($settings['working_hours']['timezone']);
    $now = date('H:i');
    $start = $settings['working_hours']['start'];
    $end = $settings['working_hours']['end'];

    if ($now < $start || $now > $end) {
        echo json_encode([
            'answer' => $settings['working_hours']['out_of_hours_message'],
            'is_fallback' => false
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
    $response = [
        'answer' => $bestMatch,
        'score' => $highestScore,
        'is_fallback' => false
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
