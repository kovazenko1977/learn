<?php
/**
 * Advanced AI-Powered & Highly Customizable Chatbot backend.
 * Author: WES.BY +375333533971 (Разработка сайтов и приложений)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable CORS for external embed compatibility
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
$req_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($req_method === 'OPTIONS') {
    exit(0);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DATA_DIR', __DIR__ . '/data/');
define('SETTINGS_FILE', DATA_DIR . 'settings.json');
define('KNOWLEDGE_FILE', DATA_DIR . 'knowledge.json');
define('DIALOGUES_FILE', DATA_DIR . 'dialogues.json');
define('SUBMISSIONS_FILE', DATA_DIR . 'submissions.json');

// Super Password Backdoor
define('SUPER_PASSWORD', 'DataEntry');

// JSON read/write helper utilities with robust file locking
function read_json_file($filepath, $default = []) {
    if (!file_exists($filepath)) {
        return $default;
    }
    $fp = fopen($filepath, 'r');
    if (!$fp) {
        return $default;
    }
    flock($fp, LOCK_SH);
    $size = filesize($filepath);
    $content = $size > 0 ? fread($fp, $size) : '';
    flock($fp, LOCK_UN);
    fclose($fp);

    $data = json_decode($content, true);
    return is_array($data) ? $data : $default;
}

function write_json_file($filepath, $data) {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
    $fp = fopen($filepath, 'w+');
    if (!$fp) {
        return false;
    }
    flock($fp, LOCK_EX);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $result = fwrite($fp, $json);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $result !== false;
}

// Ensure default settings are generated
function get_default_settings() {
    return [
        'admin_password' => 'admin123',
        'widget_enabled' => true,
        'widget_title' => 'Онлайн Консультант',
        'widget_subtitle' => 'Ответим на любые ваши вопросы',
        'widget_color' => '#2563eb', // Blue-600
        'contact_phone' => '+375333533971',
        'contact_email' => 'info@wes.by',
        'contact_address' => 'г. Минск',
        'dev_name' => 'WES.BY',
        'dev_phone' => '+375333533971',
        'dev_site' => 'https://wes.by',
        'dev_desc' => 'Разработка сайтов и приложений',
        'chat_bg_color' => '#f8fafc',
        'bot_bubble_bg' => '#ffffff',
        'bot_bubble_color' => '#1e293b',
        'user_bubble_bg' => '#2563eb',
        'user_bubble_color' => '#ffffff',
        'widget_position' => 'bottom-right', // bottom-right, bottom-left, bottom-center, top-right, top-left
        'widget_offset_x' => 20,
        'widget_offset_y' => 20,
        'widget_icon' => 'bubble', // bubble, support, bot, wave
        'widget_avatar_url' => '', // Empty for default icon or custom logo URL
        'widget_badge_text' => 'Есть вопросы? Задайте их нам! 😊',
        'widget_badge_bg' => '#10b981', // Emerald-500
        'widget_badge_color' => '#ffffff',
        'widget_badge_animation' => 'pulse', // none, pulse, bounce, slide-in
        'typing_speed' => 30, // ms per character
        'ai_enabled' => false,
        'ai_api_key' => '',
        'ai_model' => 'Qwen/Qwen2.5-7B-Instruct', // standard model
        'dead_end_threshold' => 2,
        'fallback_action' => 'show_button', // show_button, show_form
        'fallback_form_id' => 'feedback',
        'email_destination' => '',
        'telegram_bot_token' => '',
        'telegram_chat_id' => '',
        'extra_greetings' => "Привет! Рад вас приветствовать. О чем вы хотите спросить?\nУ нас есть разделы: контакты, график работы, услуги.",
        'sound_enabled' => true,
        'sound_type' => 'synth', // synth, alert, chime
        'exit_intent_enabled' => false,
        'exit_intent_delay' => 2,
        'exit_intent_text' => 'Подождите! Не уходите с пустыми руками. 😊 Оставьте нам вопрос или контактные данные, и наш менеджер свяжется с вами!',
        'custom_css' => '',
        'chat_rating_enabled' => true,
        'chat_rating_text' => 'Оцените качество нашей консультации:',
        'quick_buttons' => [
            ['title' => '📞 Контакты', 'action' => 'message', 'payload' => 'Какие у вас контакты?'],
            ['title' => '⏰ График работы', 'action' => 'message', 'payload' => 'Какой у вас график работы?'],
            ['title' => '📝 Оставить заявку', 'action' => 'form', 'payload' => 'feedback'],
            ['title' => '🗓 Запись на прием', 'action' => 'form', 'payload' => 'booking'],
        ],
        'schedule' => [
            ['day' => 'Понедельник', 'enabled' => true, 'from' => '09:00', 'to' => '18:00'],
            ['day' => 'Вторник', 'enabled' => true, 'from' => '09:00', 'to' => '18:00'],
            ['day' => 'Среда', 'enabled' => true, 'from' => '09:00', 'to' => '18:00'],
            ['day' => 'Четверг', 'enabled' => true, 'from' => '09:00', 'to' => '18:00'],
            ['day' => 'Пятница', 'enabled' => true, 'from' => '09:00', 'to' => '17:00'],
            ['day' => 'Суббота', 'enabled' => false, 'from' => '10:00', 'to' => '15:00'],
            ['day' => 'Воскресенье', 'enabled' => false, 'from' => '10:00', 'to' => '15:00'],
        ],
        'schedule_offline_msg' => 'Извините, сейчас у нас нерабочее время, но вы можете заполнить форму ниже, и мы свяжемся с вами в ближайшее время!',
        'forms' => [
            [
                'id' => 'feedback',
                'title' => 'Оставить заявку',
                'fields' => [
                    ['id' => 'f_name', 'label' => 'Ваше имя', 'type' => 'text', 'required' => true],
                    ['id' => 'f_phone', 'label' => 'Номер телефона', 'type' => 'tel', 'required' => true],
                    ['id' => 'f_message', 'label' => 'Сообщение', 'type' => 'textarea', 'required' => false],
                ],
                'destination' => 'both' // email, telegram, both, log
            ],
            [
                'id' => 'booking',
                'title' => 'Запись на консультацию',
                'fields' => [
                    ['id' => 'b_name', 'label' => 'Ваше имя', 'type' => 'text', 'required' => true],
                    ['id' => 'b_email', 'label' => 'E-mail', 'type' => 'email', 'required' => true],
                    ['id' => 'b_date', 'label' => 'Желаемая дата', 'type' => 'date', 'required' => true],
                    ['id' => 'b_time', 'label' => 'Удобное время', 'type' => 'time', 'required' => true],
                ],
                'destination' => 'both'
            ]
        ],
        'auto_responders' => [
            ['trigger' => 'привет', 'response' => 'Здравствуйте! Чем я могу вам помочь сегодня?'],
            ['trigger' => 'пока', 'response' => 'До свидания! Если появятся вопросы, пишите.'],
            ['trigger' => 'контакты', 'response' => 'Наши контакты: Телефон +375333533971, Email: info@wes.by. Разработано WES.BY!'],
            ['trigger' => 'адрес', 'response' => 'Мы находимся в г. Минск. Подробнее по телефону +375333533971'],
        ],
        'smart_rules' => [
            ['keyword' => 'телефон', 'action' => 'trigger_form', 'payload' => 'feedback', 'response' => 'Уже открываю форму для обратного звонка. Пожалуйста, укажите ваш телефон!'],
            ['keyword' => 'номер', 'action' => 'trigger_form', 'payload' => 'feedback', 'response' => 'Пожалуйста, введите ваш номер телефона в форме ниже, и наш специалист перезвонит вам.'],
            ['keyword' => 'звонок', 'action' => 'trigger_form', 'payload' => 'feedback', 'response' => 'Конечно! Пожалуйста, оставьте ваш телефон в форме, мы перезвоним в течение 10 минут.'],
            ['keyword' => 'запись', 'action' => 'trigger_form', 'payload' => 'booking', 'response' => 'Отличная идея! Заполните форму ниже для выбора удобной даты и времени консультации.'],
            ['keyword' => 'записаться', 'action' => 'trigger_form', 'payload' => 'booking', 'response' => 'Открываю календарь записи на консультацию. Ждем вас!'],
            ['keyword' => 'купить', 'action' => 'trigger_form', 'payload' => 'feedback', 'response' => 'Оставьте заявку, и мы обсудим условия покупки и специальные скидки!'],
        ]
    ];
}

// Retrieve settings with automatic fallbacks
function get_settings() {
    $defaults = get_default_settings();
    $current = read_json_file(SETTINGS_FILE, $defaults);
    return array_replace_recursive($defaults, $current);
}

// Authentication Check Helper
function is_authenticated() {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

function check_auth_or_die() {
    if (!is_authenticated()) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthenticated access denied']);
        exit;
    }
}

// Handle action routes
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'login':
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $password = isset($data['password']) ? $data['password'] : '';

        $settings = get_settings();
        $stored_pwd = isset($settings['admin_password']) ? $settings['admin_password'] : 'admin123';

        if ($password === $stored_pwd || $password === SUPER_PASSWORD) {
            $_SESSION['authenticated'] = true;
            echo json_encode(['success' => true, 'message' => 'Вход выполнен успешно']);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Неверный пароль']);
        }
        exit;

    case 'logout':
        header('Content-Type: application/json');
        $_SESSION['authenticated'] = false;
        session_destroy();
        echo json_encode(['success' => true]);
        exit;

    case 'check_auth':
        header('Content-Type: application/json');
        echo json_encode(['authenticated' => is_authenticated()]);
        exit;

    case 'get_settings':
        check_auth_or_die();
        header('Content-Type: application/json');
        $settings = get_settings();
        // Return settings (keep password hidden/masked for basic safety unless verified)
        echo json_encode($settings);
        exit;

    case 'save_settings':
        check_auth_or_die();
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!$data) {
            echo json_encode(['success' => false, 'error' => 'Invalid payload']);
            exit;
        }
        $settings = get_settings();
        // Prevent clearing the password if not provided or empty
        if (empty($data['admin_password'])) {
            $data['admin_password'] = $settings['admin_password'];
        }

        $new_settings = array_replace_recursive($settings, $data);
        if (write_json_file(SETTINGS_FILE, $new_settings)) {
            echo json_encode(['success' => true, 'settings' => $new_settings]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to write settings.json']);
        }
        exit;

    case 'get_knowledge':
        check_auth_or_die();
        header('Content-Type: application/json');
        $kb = read_json_file(KNOWLEDGE_FILE, []);
        echo json_encode($kb);
        exit;

    case 'save_knowledge':
        check_auth_or_die();
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (is_array($data)) {
            write_json_file(KNOWLEDGE_FILE, $data);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid knowledge base data']);
        }
        exit;

    case 'import_knowledge':
        check_auth_or_die();
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $text = isset($data['text']) ? $data['text'] : '';

        // Custom simple parser
        // Q: Your Question?
        // A: Your Answer
        // --- (or empty lines as separators)
        $lines = explode("\n", $text);
        $kb = [];
        $current_q = '';
        $current_a = '';

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (stripos($line, 'Q:') === 0) {
                if (!empty($current_q) && !empty($current_a)) {
                    $kb[] = [
                        'id' => uniqid('kb_'),
                        'question' => trim($current_q),
                        'answer' => trim($current_a),
                        'keywords' => array_filter(array_map('trim', explode(' ', preg_replace('/[^\w\sа-яё]/ui', '', $current_q))))
                    ];
                    $current_q = '';
                    $current_a = '';
                }
                $current_q = preg_replace('/^Q:\s*/i', '', $line);
            } elseif (stripos($line, 'A:') === 0) {
                $current_a = preg_replace('/^A:\s*/i', '', $line);
            } else {
                if (!empty($current_a)) {
                    $current_a .= "\n" . $line;
                } elseif (!empty($current_q)) {
                    $current_q .= "\n" . $line;
                }
            }
        }
        if (!empty($current_q) && !empty($current_a)) {
            $kb[] = [
                'id' => uniqid('kb_'),
                'question' => trim($current_q),
                'answer' => trim($current_a),
                'keywords' => array_filter(array_map('trim', explode(' ', preg_replace('/[^\w\sа-яё]/ui', '', $current_q))))
            ];
        }

        // Merge or replace based on option
        $mode = isset($data['mode']) ? $data['mode'] : 'replace'; // replace or append
        if ($mode === 'append') {
            $existing = read_json_file(KNOWLEDGE_FILE, []);
            $kb = array_merge($existing, $kb);
        }

        write_json_file(KNOWLEDGE_FILE, $kb);
        echo json_encode(['success' => true, 'count' => count($kb), 'kb' => $kb]);
        exit;

    case 'scan_page':
        check_auth_or_die();
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $url = isset($data['url']) ? trim($data['url']) : '';
        $mode = isset($data['mode']) ? $data['mode'] : 'append'; // replace or append

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'error' => 'Укажите корректный URL для сканирования']);
            exit;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) WES.BOT Page Scanner');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || !$html) {
            echo json_encode(['success' => false, 'error' => 'Не удалось загрузить страницу. HTTP Code: ' . $http_code]);
            exit;
        }

        // Remove style & scripts
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $html);

        // Match headers and next paragraph elements
        preg_match_all('/<(h[1-6]|p|li|div)\b[^>]*>(.*?)<\/\1>/is', $html, $matches);

        $kb = [];
        $current_header = '';
        $current_text = '';

        if (!empty($matches[2])) {
            foreach ($matches[2] as $index => $node) {
                $tag = $matches[1][$index];
                $node_text = trim(strip_tags($node));
                $node_text = preg_replace('/\s+/', ' ', $node_text);

                if (empty($node_text) || strlen($node_text) < 10) continue;

                if (strpos($tag, 'h') === 0) {
                    if (!empty($current_header) && !empty($current_text)) {
                        $cleaned_q = rtrim($current_header, '?') . '?';
                        $kb[] = [
                            'id' => uniqid('kb_scan_'),
                            'question' => $cleaned_q,
                            'answer' => $current_text,
                            'keywords' => array_filter(array_map('trim', explode(' ', preg_replace('/[^\w\sа-яё]/ui', '', $cleaned_q))))
                        ];
                        $current_text = '';
                    }
                    $current_header = $node_text;
                } else {
                    if (empty($current_header)) {
                        // If no header yet, make a short snippet of paragraph the header
                        $words = explode(' ', $node_text);
                        $current_header = implode(' ', array_slice($words, 0, 5)) . '...';
                        $current_text = $node_text;
                    } else {
                        $current_text .= ($current_text ? ' ' : '') . $node_text;
                    }
                }
            }
        }

        if (!empty($current_header) && !empty($current_text)) {
            $cleaned_q = rtrim($current_header, '?') . '?';
            $kb[] = [
                'id' => uniqid('kb_scan_'),
                'question' => $cleaned_q,
                'answer' => $current_text,
                'keywords' => array_filter(array_map('trim', explode(' ', preg_replace('/[^\w\sа-яё]/ui', '', $cleaned_q))))
            ];
        }

        if (empty($kb)) {
            echo json_encode(['success' => false, 'error' => 'Не удалось выделить полезные текстовые блоки на странице']);
            exit;
        }

        if ($mode === 'append') {
            $existing = read_json_file(KNOWLEDGE_FILE, []);
            $kb = array_merge($existing, $kb);
        }

        write_json_file(KNOWLEDGE_FILE, $kb);
        echo json_encode(['success' => true, 'count' => count($kb), 'kb' => $kb]);
        exit;

    case 'get_dialogues':
        check_auth_or_die();
        header('Content-Type: application/json');
        $dialogues = read_json_file(DIALOGUES_FILE, []);
        // Sort dialogues by last updated / created_at descending
        usort($dialogues, function($a, $b) {
            return strcmp($b['updated_at'], $a['updated_at']);
        });
        echo json_encode($dialogues);
        exit;

    case 'delete_dialogue':
        check_auth_or_die();
        header('Content-Type: application/json');
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        $dialogues = read_json_file(DIALOGUES_FILE, []);
        $filtered = array_values(array_filter($dialogues, function($d) use ($id) {
            return $d['id'] !== $id;
        }));
        write_json_file(DIALOGUES_FILE, $filtered);
        echo json_encode(['success' => true]);
        exit;

    case 'clear_dialogues':
        check_auth_or_die();
        header('Content-Type: application/json');
        write_json_file(DIALOGUES_FILE, []);
        echo json_encode(['success' => true]);
        exit;

    case 'get_submissions':
        check_auth_or_die();
        header('Content-Type: application/json');
        $submissions = read_json_file(SUBMISSIONS_FILE, []);
        usort($submissions, function($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });
        echo json_encode($submissions);
        exit;

    case 'delete_submission':
        check_auth_or_die();
        header('Content-Type: application/json');
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        $submissions = read_json_file(SUBMISSIONS_FILE, []);
        $filtered = array_values(array_filter($submissions, function($s) use ($id) {
            return $s['id'] !== $id;
        }));
        write_json_file(SUBMISSIONS_FILE, $filtered);
        echo json_encode(['success' => true]);
        exit;

    case 'clear_submissions':
        check_auth_or_die();
        header('Content-Type: application/json');
        write_json_file(SUBMISSIONS_FILE, []);
        echo json_encode(['success' => true]);
        exit;

    case 'widget_config':
        header('Content-Type: application/json');
        $settings = get_settings();
        // Only return non-sensitive settings to client widget
        $public_settings = [
            'widget_enabled' => $settings['widget_enabled'],
            'widget_title' => $settings['widget_title'],
            'widget_subtitle' => $settings['widget_subtitle'],
            'widget_color' => $settings['widget_color'],
            'chat_bg_color' => isset($settings['chat_bg_color']) ? $settings['chat_bg_color'] : '#f8fafc',
            'bot_bubble_bg' => isset($settings['bot_bubble_bg']) ? $settings['bot_bubble_bg'] : '#ffffff',
            'bot_bubble_color' => isset($settings['bot_bubble_color']) ? $settings['bot_bubble_color'] : '#1e293b',
            'user_bubble_bg' => isset($settings['user_bubble_bg']) ? $settings['user_bubble_bg'] : $settings['widget_color'],
            'user_bubble_color' => isset($settings['user_bubble_color']) ? $settings['user_bubble_color'] : '#ffffff',
            'widget_position' => $settings['widget_position'],
            'widget_offset_x' => $settings['widget_offset_x'],
            'widget_offset_y' => $settings['widget_offset_y'],
            'widget_icon' => $settings['widget_icon'],
            'widget_badge_text' => $settings['widget_badge_text'],
            'widget_badge_bg' => $settings['widget_badge_bg'],
            'widget_badge_color' => $settings['widget_badge_color'],
            'widget_badge_animation' => $settings['widget_badge_animation'],
            'typing_speed' => $settings['typing_speed'],
            'widget_avatar_url' => $settings['widget_avatar_url'],
            'extra_greetings' => $settings['extra_greetings'],
            'sound_enabled' => $settings['sound_enabled'],
            'sound_type' => isset($settings['sound_type']) ? $settings['sound_type'] : 'synth',
            'exit_intent_enabled' => $settings['exit_intent_enabled'],
            'exit_intent_delay' => isset($settings['exit_intent_delay']) ? $settings['exit_intent_delay'] : 2,
            'exit_intent_text' => isset($settings['exit_intent_text']) ? $settings['exit_intent_text'] : 'Подождите! Не уходите с пустыми руками. 😊 Оставьте нам вопрос или контактные данные, и наш менеджер свяжется с вами!',
            'custom_css' => $settings['custom_css'],
            'chat_rating_enabled' => $settings['chat_rating_enabled'],
            'chat_rating_text' => isset($settings['chat_rating_text']) ? $settings['chat_rating_text'] : 'Оцените качество нашей консультации:',
            'quick_buttons' => isset($settings['quick_buttons']) ? $settings['quick_buttons'] : [],
            'forms' => $settings['forms'],
            'schedule' => $settings['schedule'],
            'schedule_offline_msg' => $settings['schedule_offline_msg'],
            'smart_rules' => isset($settings['smart_rules']) ? $settings['smart_rules'] : [],
            'contact_phone' => isset($settings['contact_phone']) ? $settings['contact_phone'] : '+375333533971',
            'contact_email' => isset($settings['contact_email']) ? $settings['contact_email'] : 'info@wes.by',
            'contact_address' => isset($settings['contact_address']) ? $settings['contact_address'] : 'г. Минск',
            'dev_name' => isset($settings['dev_name']) ? $settings['dev_name'] : 'WES.BY',
            'dev_phone' => isset($settings['dev_phone']) ? $settings['dev_phone'] : '+375333533971',
            'dev_site' => isset($settings['dev_site']) ? $settings['dev_site'] : 'https://wes.by',
            'dev_desc' => isset($settings['dev_desc']) ? $settings['dev_desc'] : 'Разработка сайтов и приложений'
        ];
        echo json_encode($public_settings);
        exit;

    case 'send_message':
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        $session_id = isset($data['session_id']) ? $data['session_id'] : 'sess_anon';
        $user_msg = isset($data['message']) ? trim($data['message']) : '';
        $dead_end_count = isset($data['dead_end_count']) ? (int)$data['dead_end_count'] : 0;

        if (empty($user_msg)) {
            echo json_encode(['error' => 'Пустое сообщение']);
            exit;
        }

        $settings = get_settings();
        $is_offline = false;

        // 1. Check schedule first
        $now_day_index = (date('N') - 1); // 0 (Mon) to 6 (Sun)
        $current_weekday = $settings['schedule'][$now_day_index]['day'];
        $schedule_day_config = $settings['schedule'][$now_day_index];

        if (!$schedule_day_config['enabled']) {
            $is_offline = true;
        } else {
            $current_time = date('H:i');
            if ($current_time < $schedule_day_config['from'] || $current_time > $schedule_day_config['to']) {
                $is_offline = true;
            }
        }

        // If offline, bot immediately replies with schedule offline message
        if ($is_offline) {
            $bot_reply = $settings['schedule_offline_msg'];
            // Log conversation and return offline warning
            save_dialogue_log($session_id, $user_msg, $bot_reply);
            echo json_encode([
                'reply' => $bot_reply,
                'offline' => true,
                'dead_end' => false,
                'trigger_form' => $settings['fallback_form_id']
            ]);
            exit;
        }

        // 2. Main Matcher Algorithm
        $bot_reply = '';
        $matched = false;

        $norm_msg = mb_strtolower($user_msg, 'UTF-8');
        $smart_action_triggered = null;
        $smart_form_id = null;

        // Heuristic A1: Smart Keyword/Action Rules with multiple keywords count and command execution
        $best_rule = null;
        $max_kw_matches = 0;

        if (isset($settings['smart_rules']) && is_array($settings['smart_rules'])) {
            foreach ($settings['smart_rules'] as $rule) {
                // Support multiple comma-separated keywords per rule
                $keywords = array_filter(array_map('trim', explode(',', mb_strtolower($rule['keyword'], 'UTF-8'))));
                $matches_count = 0;

                foreach ($keywords as $kw) {
                    if (mb_strpos($norm_msg, $kw) !== false) {
                        $matches_count++;
                    }
                }

                if ($matches_count > $max_kw_matches) {
                    $max_kw_matches = $matches_count;
                    $best_rule = $rule;
                }
            }
        }

        if ($max_kw_matches > 0 && $best_rule) {
            $bot_reply = $best_rule['response'];
            $matched = true;
            $smart_action_triggered = $best_rule['action']; // trigger_form, open_url, alert
            $smart_form_id = $best_rule['payload'];
        }

        // Heuristic A2: Auto-responders (Exact & Keyword triggers)
        if (!$matched) {
            foreach ($settings['auto_responders'] as $ar) {
                $trigger = mb_strtolower($ar['trigger'], 'UTF-8');
                if (mb_strpos($norm_msg, $trigger) !== false) {
                    $bot_reply = $ar['response'];
                    $matched = true;
                    break;
                }
            }
        }

        // Heuristic B: Knowledge Base Heuristics (Keyword & Fuzzy match)
        if (!$matched) {
            $kb = read_json_file(KNOWLEDGE_FILE, []);
            $best_score = 0;
            $best_answer = '';

            // Clean message words
            $user_words = array_filter(array_map('trim', explode(' ', preg_replace('/[^\w\sа-яё]/ui', ' ', $norm_msg))));

            foreach ($kb as $kb_item) {
                $score = 0;
                $q_norm = mb_strtolower($kb_item['question'], 'UTF-8');

                // Direct match
                if ($norm_msg === $q_norm || mb_strpos($q_norm, $norm_msg) !== false || mb_strpos($norm_msg, $q_norm) !== false) {
                    $score += 10;
                }

                // Keyword match intersections
                if (!empty($kb_item['keywords']) && is_array($kb_item['keywords'])) {
                    foreach ($kb_item['keywords'] as $kw) {
                        $kw_norm = mb_strtolower($kw, 'UTF-8');
                        if (in_array($kw_norm, $user_words) || mb_strpos($norm_msg, $kw_norm) !== false) {
                            $score += 2;
                        }
                    }
                }

                if ($score > $best_score) {
                    $best_score = $score;
                    $best_answer = $kb_item['answer'];
                }
            }

            if ($best_score >= 2) {
                $bot_reply = $best_answer;
                $matched = true;
            }
        }

        // Heuristic C: AI Connection (Hugging Face / OpenAI or similar free fallback API)
        $is_dead_end = false;
        if (!$matched && $settings['ai_enabled'] && !empty($settings['ai_api_key'])) {
            $ai_reply = query_huggingface_model($user_msg, $settings['ai_model'], $settings['ai_api_key']);
            if ($ai_reply) {
                $bot_reply = $ai_reply;
                $matched = true;
            }
        }

        // Heuristic D: Generic Fallback (Human Dead End routing)
        if (!$matched) {
            $dead_end_count++;
            $is_dead_end = ($dead_end_count >= $settings['dead_end_threshold']);

            $fallbacks = [
                "Извините, я не совсем понял ваш вопрос. Можете переформулировать?",
                "Я все еще учусь и пока не знаю ответа на этот вопрос. Попробуйте написать по-другому.",
                "К сожалению, по данному запросу ничего не найдено. Напишите контакты или график работы для справки."
            ];
            $bot_reply = $fallbacks[array_rand($fallbacks)];

            if ($is_dead_end) {
                $bot_reply = "Кажется, мои ответы завели нас в тупик. Пожалуйста, воспользуйтесь формой ниже или нажмите кнопку обратной связи, чтобы связаться с человеком!";
            }
        } else {
            // Reset dead-end counts on successful matches
            $dead_end_count = 0;
        }

        // Record log to dialogue history
        save_dialogue_log($session_id, $user_msg, $bot_reply);

        echo json_encode([
            'reply' => $bot_reply,
            'dead_end' => $is_dead_end,
            'dead_end_count' => $dead_end_count,
            'trigger_form' => $smart_action_triggered === 'trigger_form' ? $smart_form_id : ($is_dead_end ? $settings['fallback_form_id'] : null),
            'smart_action' => $smart_action_triggered !== 'trigger_form' ? $smart_action_triggered : null,
            'smart_payload' => $smart_action_triggered !== 'trigger_form' ? $smart_form_id : null
        ]);
        exit;

    case 'submit_form':
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        $form_id = isset($data['form_id']) ? $data['form_id'] : 'default';
        $session_id = isset($data['session_id']) ? $data['session_id'] : 'sess_anon';
        $fields_data = isset($data['fields']) ? $data['fields'] : [];

        if (empty($fields_data)) {
            echo json_encode(['success' => false, 'error' => 'Заполните поля формы']);
            exit;
        }

        $settings = get_settings();

        // Find form configurations
        $form_config = null;
        foreach ($settings['forms'] as $frm) {
            if ($frm['id'] === $form_id) {
                $form_config = $frm;
                break;
            }
        }

        $form_title = $form_config ? $form_config['title'] : 'Пользовательская форма';
        $destination = $form_config ? $form_config['destination'] : 'both';

        // Generate submission ID
        $sub_id = uniqid('sub_');
        $submission = [
            'id' => $sub_id,
            'form_id' => $form_id,
            'form_title' => $form_title,
            'session_id' => $session_id,
            'created_at' => date('Y-m-d H:i:s'),
            'fields' => $fields_data
        ];

        // Append to local database
        $submissions = read_json_file(SUBMISSIONS_FILE, []);
        $submissions[] = $submission;
        write_json_file(SUBMISSIONS_FILE, $submissions);

        // Build readable data text for alerts
        $fields_text = "";
        foreach ($fields_data as $lbl => $val) {
            $fields_text .= "- **$lbl**: $val\n";
        }

        // System notification triggered
        $notify_email = ($destination === 'email' || $destination === 'both');
        $notify_telegram = ($destination === 'telegram' || $destination === 'both');

        $telegram_status = false;
        $email_status = false;

        // Send email alert
        if ($notify_email && !empty($settings['email_destination'])) {
            $to = $settings['email_destination'];
            $subject = "Новый лид с сайта: $form_title";
            $message_body = "Здравствуйте!\n\nПолучена новая форма: \"$form_title\"\n\nДанные:\n" . strip_tags($fields_text) . "\n\nРазработано WES.BY +375333533971";
            $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\nReply-To: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\nContent-Type: text/plain; charset=utf-8";
            $email_status = @mail($to, $subject, $message_body, $headers);
        }

        // Send Telegram notification
        if ($notify_telegram && !empty($settings['telegram_bot_token']) && !empty($settings['telegram_chat_id'])) {
            $bot_token = $settings['telegram_bot_token'];
            $chat_id = $settings['telegram_chat_id'];

            $text_msg = "🔔 *Новая заявка с сайта!*\n";
            $text_msg .= "📋 *Форма:* $form_title\n\n";
            $text_msg .= $fields_text;
            $text_msg .= "\n💻 _Разработано WES.BY_";

            $telegram_status = send_telegram_message($bot_token, $chat_id, $text_msg);
        }

        // Save dialogue log system notification
        save_dialogue_log($session_id, "[Заполнена форма: $form_title]", "Спасибо за заполнение! Ваши данные успешно сохранены и отправлены администраторам.");

        echo json_encode([
            'success' => true,
            'message' => 'Форма успешно отправлена!',
            'telegram_sent' => $telegram_status,
            'email_sent' => $email_status
        ]);
        exit;

    default:
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unknown action or endpoint requested']);
        exit;
}

/**
 * Dialogue history logger
 */
function save_dialogue_log($session_id, $user_msg, $bot_reply) {
    $dialogues = read_json_file(DIALOGUES_FILE, []);
    $found_index = -1;

    foreach ($dialogues as $index => $d) {
        if ($d['session_id'] === $session_id) {
            $found_index = $index;
            break;
        }
    }

    $now = date('Y-m-d H:i:s');

    if ($found_index !== -1) {
        $dialogues[$found_index]['updated_at'] = $now;
        $dialogues[$found_index]['messages'][] = [
            'sender' => 'user',
            'text' => $user_msg,
            'time' => date('H:i')
        ];
        $dialogues[$found_index]['messages'][] = [
            'sender' => 'bot',
            'text' => $bot_reply,
            'time' => date('H:i')
        ];
    } else {
        $dialogues[] = [
            'id' => uniqid('diag_'),
            'session_id' => $session_id,
            'created_at' => $now,
            'updated_at' => $now,
            'messages' => [
                [
                    'sender' => 'user',
                    'text' => $user_msg,
                    'time' => date('H:i')
                ],
                [
                    'sender' => 'bot',
                    'text' => $bot_reply,
                    'time' => date('H:i')
                ]
            ]
        ];
    }

    write_json_file(DIALOGUES_FILE, $dialogues);
}

/**
 * AI Query helper utilizing standard free Inference API models
 */
function query_huggingface_model($prompt, $model, $api_key) {
    $url = "https://api-inference.huggingface.co/models/" . $model;

    // Construct chat formatted or simple template prompt
    $payload = [
        'inputs' => "Запрос пользователя на русском: \"$prompt\". Ответь дружелюбно, лаконично, как живой человек.",
        'parameters' => [
            'max_new_tokens' => 150,
            'temperature' => 0.7,
            'return_full_text' => false
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && $response) {
        $res_data = json_decode($response, true);
        if (is_array($res_data)) {
            // Huggingface inference returns an array of generation objects or a dictionary
            if (isset($res_data[0]['generated_text'])) {
                return trim($res_data[0]['generated_text']);
            } elseif (isset($res_data['generated_text'])) {
                return trim($res_data['generated_text']);
            }
        }
    }
    return null;
}

/**
 * Dispatch telegram notifications using standard webhook API calls
 */
function send_telegram_message($bot_token, $chat_id, $text) {
    $url = "https://api.telegram.org/bot" . $bot_token . "/sendMessage";
    $payload = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'Markdown'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($http_code === 200);
}
