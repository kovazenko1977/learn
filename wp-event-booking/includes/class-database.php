<?php
/**
 * Database handler and storage provider for WP Event Booking.
 * Supports WordPress $wpdb when running inside WordPress,
 * and SQLite / JSON fallback when running standalone or during automated headless preview.
 */

if (!defined('ABSPATH') && !defined('WPEB_STANDALONE')) {
    exit;
}

class WPEB_Database {
    private static $instance = null;
    private $pdo = null;
    private $is_wp = false;
    private $prefix = 'wpeb_';

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        if (isset($wpdb) && is_object($wpdb) && method_exists($wpdb, 'get_results')) {
            $this->is_wp = true;
            $this->prefix = $wpdb->prefix . 'wpeb_';
        } else {
            $this->is_wp = false;
            $this->init_sqlite();
        }
    }

    private function init_sqlite() {
        $data_dir = dirname(__DIR__, 2) . '/data';
        if (!is_dir($data_dir)) {
            @mkdir($data_dir, 0777, true);
        }
        $db_path = $data_dir . '/event_booking.sqlite';
        $this->pdo = new PDO('sqlite:' . $db_path);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->create_sqlite_tables();
    }

    public function create_tables() {
        if ($this->is_wp) {
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "
            CREATE TABLE IF NOT EXISTS {$this->prefix}events (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                description TEXT NULL,
                location VARCHAR(255) NULL,
                price DECIMAL(10,2) DEFAULT 0.00,
                currency VARCHAR(10) DEFAULT 'RUB',
                status VARCHAR(50) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;

            CREATE TABLE IF NOT EXISTS {$this->prefix}slots (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                event_id BIGINT(20) UNSIGNED NOT NULL,
                slot_year INT(4) NOT NULL,
                slot_month INT(2) NOT NULL,
                slot_date VARCHAR(10) NOT NULL, -- YYYY-MM-DD
                slot_time VARCHAR(20) NOT NULL, -- HH:MM (e.g. 10:00, 14:30)
                max_capacity INT(11) NOT NULL DEFAULT 10,
                booked_count INT(11) NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY event_date (event_id, slot_date)
            ) $charset_collate;

            CREATE TABLE IF NOT EXISTS {$this->prefix}bookings (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                event_id BIGINT(20) UNSIGNED NOT NULL,
                slot_id BIGINT(20) UNSIGNED NOT NULL,
                booking_code VARCHAR(32) NOT NULL,
                client_name VARCHAR(255) NOT NULL,
                client_email VARCHAR(255) NOT NULL,
                client_phone VARCHAR(50) NOT NULL,
                ticket_count INT(11) NOT NULL DEFAULT 1,
                custom_fields LONGTEXT NULL,
                status VARCHAR(50) DEFAULT 'confirmed',
                check_in TINYINT(1) DEFAULT 0,
                admin_notes TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY booking_code (booking_code)
            ) $charset_collate;

            CREATE TABLE IF NOT EXISTS {$this->prefix}forms (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                description TEXT NULL,
                fields_json LONGTEXT NULL,
                settings_json LONGTEXT NULL,
                status VARCHAR(50) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY slug (slug)
            ) $charset_collate;

            CREATE TABLE IF NOT EXISTS {$this->prefix}form_fields (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                event_id BIGINT(20) UNSIGNED DEFAULT 0,
                field_key VARCHAR(100) NOT NULL,
                field_label VARCHAR(255) NOT NULL,
                field_type VARCHAR(50) NOT NULL,
                field_options TEXT NULL,
                placeholder VARCHAR(255) NULL,
                is_required TINYINT(1) DEFAULT 0,
                sort_order INT(11) DEFAULT 0,
                PRIMARY KEY (id)
            ) $charset_collate;

            CREATE TABLE IF NOT EXISTS {$this->prefix}settings (
                setting_key VARCHAR(100) NOT NULL,
                setting_value LONGTEXT NULL,
                PRIMARY KEY (setting_key)
            ) $charset_collate;
            ";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            $this->seed_default_data();
        } else {
            $this->create_sqlite_tables();
        }
    }

    private function create_sqlite_tables() {
        if (!$this->pdo) return;

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT NOT NULL,
                description TEXT,
                location TEXT,
                price REAL DEFAULT 0.00,
                currency TEXT DEFAULT 'RUB',
                status TEXT DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS slots (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_id INTEGER NOT NULL,
                slot_year INTEGER NOT NULL,
                slot_month INTEGER NOT NULL,
                slot_date TEXT NOT NULL,
                slot_time TEXT NOT NULL,
                max_capacity INTEGER NOT NULL DEFAULT 10,
                booked_count INTEGER NOT NULL DEFAULT 0,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS bookings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_id INTEGER NOT NULL,
                slot_id INTEGER NOT NULL,
                booking_code TEXT NOT NULL UNIQUE,
                client_name TEXT NOT NULL,
                client_email TEXT NOT NULL,
                client_phone TEXT NOT NULL,
                ticket_count INTEGER NOT NULL DEFAULT 1,
                custom_fields TEXT,
                status TEXT DEFAULT 'confirmed',
                check_in INTEGER DEFAULT 0,
                admin_notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS forms (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT,
                fields_json TEXT,
                settings_json TEXT,
                status TEXT DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS form_fields (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_id INTEGER DEFAULT 0,
                field_key TEXT NOT NULL,
                field_label TEXT NOT NULL,
                field_type TEXT NOT NULL,
                field_options TEXT,
                placeholder TEXT,
                is_required INTEGER DEFAULT 0,
                sort_order INTEGER DEFAULT 0
            );

            CREATE TABLE IF NOT EXISTS settings (
                setting_key TEXT PRIMARY KEY,
                setting_value TEXT
            );
        ");

        $this->seed_default_data();
    }

    public function seed_default_data() {
        // Check if settings exist, otherwise insert defaults
        $pin = $this->get_setting('admin_pin', null);
        if ($pin === null) {
            $default_settings = [
                'admin_pin' => '222120',
                'telegram_token' => '',
                'telegram_chat_id' => '',
                'telegram_enabled' => '0',
                'telegram_template' => "⚡ Новая запись на мероприятие!\n\n📅 Дата: {date} в {time}\n🎪 Событие: {event_title}\n👤 Клиент: {name}\n📞 Телефон: {phone}\n📧 Email: {email}\n🎟 Билетов: {tickets}\n🔖 Код бронирования: #{code}",
                'email_enabled' => '1',
                'email_to' => 'admin@example.com',
                'email_from_name' => 'Онлайн Запись на Мероприятия',
                'email_subject' => 'Подтверждение записи на мероприятие #{code}',
                'email_template' => "Здравствуйте, {name}!\n\nВаша запись успешно подтверждена.\nМероприятие: {event_title}\nДата: {date}\nВремя: {time}\nКоличество мест: {tickets}\nНомер брони: {code}\n\nСпасибо, что выбрали нас!",
                // Visual & mobile customizer settings
                'primary_color' => '#3b82f6',
                'accent_color' => '#10b981',
                'bg_color' => '#ffffff',
                'text_color' => '#1f2937',
                'border_radius' => '12',
                'font_family' => 'Inter, -apple-system, sans-serif',
                'enable_dark_mode' => '1',
                'custom_css' => '',
                'enable_qr_code' => '1',
                'enable_ical_export' => '1',
                'enable_promo_codes' => '1',
                'auto_confirm' => '1'
            ];

            foreach ($default_settings as $key => $val) {
                $this->update_setting($key, $val);
            }
        }

        // Check if forms exist, seed pre-built ready templates
        $this->seed_default_forms();

        // Check if events exist
        $events = $this->get_events();
        if (empty($events)) {
            $eventId = $this->insert_event([
                'title' => 'Мастер-класс: Искусство публичных выступлений',
                'slug' => 'public-speaking-masterclass',
                'description' => 'Практический интенсив по ораторскому мастерству, самопрезентации и управлению вниманием аудитории.',
                'location' => 'Москва, Пресненская наб., 12 / Онлайн-трансляция',
                'price' => 1500.00,
                'currency' => 'RUB',
                'status' => 'active'
            ]);

            // Add default form fields
            $this->insert_form_field([
                'event_id' => $eventId,
                'field_key' => 'organization',
                'field_label' => 'Организация / Компания',
                'field_type' => 'text',
                'field_options' => '',
                'placeholder' => 'ООО «Технологии Будущего»',
                'is_required' => 0,
                'sort_order' => 1
            ]);

            $this->insert_form_field([
                'event_id' => $eventId,
                'field_key' => 'format_preference',
                'field_label' => 'Формат участия',
                'field_type' => 'radio',
                'field_options' => "Очно в зале\nОнлайн трансляция (Zoom)",
                'placeholder' => '',
                'is_required' => 1,
                'sort_order' => 2
            ]);

            $this->insert_form_field([
                'event_id' => $eventId,
                'field_key' => 'comment',
                'field_label' => 'Пожелания или вопросы спикеру',
                'field_type' => 'textarea',
                'field_options' => '',
                'placeholder' => 'Напишите, что для вас особенно актуально...',
                'is_required' => 0,
                'sort_order' => 3
            ]);

            // Seed slots for the current month and next month
            $this->generate_default_slots_for_event($eventId);
        }
    }

    public function generate_default_slots_for_event($eventId) {
        $now = new DateTime('now');
        for ($monthOffset = 0; $monthOffset <= 2; $monthOffset++) {
            $currentMonthDate = (clone $now)->modify("+{$monthOffset} month");
            $year = (int)$currentMonthDate->format('Y');
            $month = (int)$currentMonthDate->format('n');
            $daysInMonth = (int)$currentMonthDate->format('t');

            // Seed slots on every 3rd day
            for ($day = 2; $day <= $daysInMonth; $day += 3) {
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                // Times
                $times = ['10:00', '12:30', '15:00', '17:30', '19:00'];
                foreach ($times as $idx => $time) {
                    $capacity = ($idx === 1) ? 2 : 5; // e.g., 12:30 has limit of 2 for testing exhaustion
                    $this->insert_slot([
                        'event_id' => $eventId,
                        'slot_year' => $year,
                        'slot_month' => $month,
                        'slot_date' => $dateStr,
                        'slot_time' => $time,
                        'max_capacity' => $capacity,
                        'booked_count' => 0,
                        'is_active' => 1
                    ]);
                }
            }
        }
    }

    // Setting Helpers
    public function get_setting($key, $default = null) {
        if ($this->is_wp) {
            global $wpdb;
            $row = $wpdb->get_row($wpdb->prepare("SELECT setting_value FROM {$this->prefix}settings WHERE setting_key = %s", $key));
            return $row ? $row->setting_value : $default;
        } else {
            $stmt = $this->pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $res = $stmt->fetchColumn();
            return ($res !== false) ? $res : $default;
        }
    }

    public function update_setting($key, $val) {
        if ($this->is_wp) {
            global $wpdb;
            $table = $this->prefix . 'settings';
            $wpdb->replace($table, [
                'setting_key' => $key,
                'setting_value' => is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : (string)$val
            ]);
        } else {
            $stmt = $this->pdo->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : (string)$val]);
        }
    }

    public function get_all_settings() {
        if ($this->is_wp) {
            global $wpdb;
            $rows = $wpdb->get_results("SELECT setting_key, setting_value FROM {$this->prefix}settings", ARRAY_A);
        } else {
            $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM settings");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $res = [];
        foreach ($rows as $r) {
            $res[$r['setting_key']] = $r['setting_value'];
        }
        return $res;
    }

    // Pre-built Form Templates Seeding
    public function seed_default_forms() {
        $forms = $this->get_forms();
        if (!empty($forms)) {
            return;
        }

        $templates = [
            [
                'title' => 'Стандартная запись на мероприятие',
                'slug' => 'standard-event',
                'description' => 'Универсальный шаблон для конференций, вебинаров, мастер-классов и открытых уроков.',
                'status' => 'active',
                'fields_json' => json_encode([
                    ['field_key' => 'organization', 'field_label' => 'Компания / Организация', 'field_type' => 'text', 'placeholder' => 'ООО «Вектор»', 'is_required' => 0, 'width' => '50'],
                    ['field_key' => 'job_title', 'field_label' => 'Должность', 'field_type' => 'text', 'placeholder' => 'Руководитель проекта', 'is_required' => 0, 'width' => '50'],
                    ['field_key' => 'format_preference', 'field_label' => 'Формат участия', 'field_type' => 'radio', 'field_options' => "Очно в зале\nОнлайн трансляция (Zoom)", 'placeholder' => '', 'is_required' => 1, 'width' => '100'],
                    ['field_key' => 'comment', 'field_label' => 'Вопросы к спикеру или пожелания', 'field_type' => 'textarea', 'placeholder' => 'Напишите, что для вас особенно важно...', 'is_required' => 0, 'width' => '100'],
                    ['field_key' => 'consent_pd', 'field_label' => 'Согласен на обработку персональных данных', 'field_type' => 'consent', 'placeholder' => '', 'is_required' => 1, 'width' => '100']
                ], JSON_UNESCAPED_UNICODE)
            ],
            [
                'title' => 'VIP / Бизнес-сессия с кейтерингом',
                'slug' => 'vip-business-session',
                'description' => 'Расширенная анкета для премиум-гостей с выбором меню питания, трансфера и статуса участника.',
                'status' => 'active',
                'fields_json' => json_encode([
                    ['field_key' => 'package_type', 'field_label' => 'Категория билета', 'field_type' => 'select', 'field_options' => "Бизнес (первые ряды)\nVIP All-Inclusive (банкет + нетворкинг)\nПартнерский пакет", 'placeholder' => '', 'is_required' => 1, 'width' => '100'],
                    ['field_key' => 'dietary_needs', 'field_label' => 'Предпочтения по кофе-брейку / банкету', 'field_type' => 'checkbox', 'field_options' => "Классическое европейское меню\nВегетарианское меню\nБезглютеновое питание\nХаляль", 'placeholder' => '', 'is_required' => 0, 'width' => '100'],
                    ['field_key' => 'need_transfer', 'field_label' => 'Необходим трансфер из аэропорта / вокзала', 'field_type' => 'switch', 'placeholder' => '', 'is_required' => 0, 'width' => '50'],
                    ['field_key' => 'telegram_username', 'field_label' => 'Ваш Telegram (@username)', 'field_type' => 'text', 'placeholder' => '@alex_smith', 'is_required' => 1, 'width' => '50'],
                    ['field_key' => 'personal_assistant', 'field_label' => 'Контакты личного ассистента (телефон / email)', 'field_type' => 'text', 'placeholder' => '+7 (999) 000-00-00, assist@co.ru', 'is_required' => 0, 'width' => '100']
                ], JSON_UNESCAPED_UNICODE)
            ],
            [
                'title' => 'Запись на консультацию и прием',
                'slug' => 'consultation-reception',
                'description' => 'Форма предварительной записи к специалисту, юристу, врачу или эксперту.',
                'status' => 'active',
                'fields_json' => json_encode([
                    ['field_key' => 'consultation_topic', 'field_label' => 'Тематика обращения', 'field_type' => 'select', 'field_options' => "Первичная консультация\nПовторный аудит\nСрочный правовой вопрос\nИндивидуальное сопровождение", 'placeholder' => '', 'is_required' => 1, 'width' => '100'],
                    ['field_key' => 'birth_date', 'field_label' => 'Дата рождения', 'field_type' => 'date', 'placeholder' => 'ДД.ММ.ГГГГ', 'is_required' => 0, 'width' => '50'],
                    ['field_key' => 'preferred_messenger', 'field_label' => 'Предпочитаемый способ связи', 'field_type' => 'radio', 'field_options' => "Звонок по телефону\nWhatsApp\nTelegram", 'placeholder' => '', 'is_required' => 1, 'width' => '50'],
                    ['field_key' => 'situation_description', 'field_label' => 'Краткое описание вашей ситуации', 'field_type' => 'textarea', 'placeholder' => 'Опишите предысторию или текущую задачу...', 'is_required' => 1, 'width' => '100']
                ], JSON_UNESCAPED_UNICODE)
            ],
            [
                'title' => 'Детские праздники и творческие мастер-классы',
                'slug' => 'kids-masterclass',
                'description' => 'Удобная форма бронирования для родителей и детей с указанием возраста и аниматора.',
                'status' => 'active',
                'fields_json' => json_encode([
                    ['field_key' => 'child_name_age', 'field_label' => 'Имя и возраст ребенка', 'field_type' => 'text', 'placeholder' => 'Максим, 8 лет', 'is_required' => 1, 'width' => '50'],
                    ['field_key' => 'parents_count', 'field_label' => 'Количество сопровождающих взрослых', 'field_type' => 'number', 'placeholder' => '1', 'is_required' => 0, 'width' => '50'],
                    ['field_key' => 'program_choice', 'field_label' => 'Выбор творческой программы', 'field_type' => 'radio', 'field_options' => "Гончарное дело и лепка\nРисование на холсте маслом\nРобототехника и LEGO\nКулинарный мастер-класс (Пицца)", 'placeholder' => '', 'is_required' => 1, 'width' => '100'],
                    ['field_key' => 'allergies_notes', 'field_label' => 'Особенности здоровья или аллергии', 'field_type' => 'text', 'placeholder' => 'Аллергия на орехи, пыльцу...', 'is_required' => 0, 'width' => '100']
                ], JSON_UNESCAPED_UNICODE)
            ],
            [
                'title' => 'Спортивные тренировки и фитнес',
                'slug' => 'fitness-training',
                'description' => 'Запись на групповые и индивидуальные спортивные занятия, йогу или кроссфит.',
                'status' => 'active',
                'fields_json' => json_encode([
                    ['field_key' => 'fitness_level', 'field_label' => 'Ваш уровень подготовки', 'field_type' => 'radio', 'field_options' => "Начинающий (первый раз)\nСредний (занимаюсь регулярно)\nПродвинутый / Атлет", 'placeholder' => '', 'is_required' => 1, 'width' => '100'],
                    ['field_key' => 'rent_gear', 'field_label' => 'Необходима аренда экипировки (коврик, пояс, перчатки)', 'field_type' => 'switch', 'placeholder' => '', 'is_required' => 0, 'width' => '50'],
                    ['field_key' => 'locker_room', 'field_label' => 'Тип раздевалки', 'field_type' => 'select', 'field_options' => "Стандартная раздевалка\nVIP-раздевалка с сауной", 'placeholder' => '', 'is_required' => 0, 'width' => '50']
                ], JSON_UNESCAPED_UNICODE)
            ]
        ];

        foreach ($templates as $tpl) {
            $this->insert_form($tpl);
        }
    }

    // Forms CRUD & Duplication
    public function get_forms($status = null) {
        if ($this->is_wp) {
            global $wpdb;
            $sql = "SELECT * FROM {$this->prefix}forms";
            if ($status) {
                $sql .= $wpdb->prepare(" WHERE status = %s", $status);
            }
            $sql .= " ORDER BY id DESC";
            return $wpdb->get_results($sql, ARRAY_A);
        } else {
            $sql = "SELECT * FROM forms";
            $params = [];
            if ($status) {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }
            $sql .= " ORDER BY id DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function get_form($idOrSlug) {
        if (is_numeric($idOrSlug)) {
            $whereCol = 'id';
            $val = (int)$idOrSlug;
        } else {
            $whereCol = 'slug';
            $val = trim((string)$idOrSlug);
        }

        if ($this->is_wp) {
            global $wpdb;
            $sql = $wpdb->prepare("SELECT * FROM {$this->prefix}forms WHERE {$whereCol} = %s", $val);
            return $wpdb->get_row($sql, ARRAY_A);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM forms WHERE {$whereCol} = ?");
            $stmt->execute([$val]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    }

    public function insert_form($data) {
        $slug = !empty($data['slug']) ? $data['slug'] : 'form-' . substr(md5(uniqid()), 0, 6);
        $fieldsJson = is_array($data['fields_json']) ? json_encode($data['fields_json'], JSON_UNESCAPED_UNICODE) : ($data['fields_json'] ?? '[]');
        $settingsJson = is_array($data['settings_json']) ? json_encode($data['settings_json'], JSON_UNESCAPED_UNICODE) : ($data['settings_json'] ?? '{}');

        if ($this->is_wp) {
            global $wpdb;
            $wpdb->insert($this->prefix . 'forms', [
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'] ?? '',
                'fields_json' => $fieldsJson,
                'settings_json' => $settingsJson,
                'status' => $data['status'] ?? 'active'
            ]);
            return $wpdb->insert_id;
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO forms (title, slug, description, fields_json, settings_json, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['title'],
                $slug,
                $data['description'] ?? '',
                $fieldsJson,
                $settingsJson,
                $data['status'] ?? 'active'
            ]);
            return (int)$this->pdo->lastInsertId();
        }
    }

    public function update_form($id, $data) {
        $fields = [];
        $values = [];
        foreach ($data as $k => $v) {
            if ($k === 'fields_json' && is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            if ($k === 'settings_json' && is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            $fields[] = "$k = ?";
            $values[] = $v;
        }
        $values[] = (int)$id;

        if ($this->is_wp) {
            global $wpdb;
            $wpdb->update($this->prefix . 'forms', $data, ['id' => $id]);
            return true;
        } else {
            $sql = "UPDATE forms SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
        }
    }

    public function duplicate_form($id) {
        $form = $this->get_form($id);
        if (!$form) return false;

        $newTitle = $form['title'] . ' (Копия)';
        $newSlug = $form['slug'] . '-copy-' . substr(md5(uniqid()), 0, 4);

        return $this->insert_form([
            'title' => $newTitle,
            'slug' => $newSlug,
            'description' => $form['description'] ?? '',
            'fields_json' => $form['fields_json'],
            'settings_json' => $form['settings_json'] ?? '{}',
            'status' => 'active'
        ]);
    }

    public function delete_form($id) {
        if ($this->is_wp) {
            global $wpdb;
            $wpdb->delete($this->prefix . 'forms', ['id' => $id]);
        } else {
            $this->pdo->prepare("DELETE FROM forms WHERE id = ?")->execute([(int)$id]);
        }
        return true;
    }

    // Events
    public function get_events($status = null) {
        if ($this->is_wp) {
            global $wpdb;
            $sql = "SELECT * FROM {$this->prefix}events";
            if ($status) {
                $sql .= $wpdb->prepare(" WHERE status = %s", $status);
            }
            $sql .= " ORDER BY id DESC";
            return $wpdb->get_results($sql, ARRAY_A);
        } else {
            $sql = "SELECT * FROM events";
            $params = [];
            if ($status) {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }
            $sql .= " ORDER BY id DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function get_event($id) {
        if ($this->is_wp) {
            global $wpdb;
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->prefix}events WHERE id = %d", $id), ARRAY_A);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    }

    public function insert_event($data) {
        if ($this->is_wp) {
            global $wpdb;
            $wpdb->insert($this->prefix . 'events', $data);
            return $wpdb->insert_id;
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO events (title, slug, description, location, price, currency, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['title'],
                $data['slug'] ?? '',
                $data['description'] ?? '',
                $data['location'] ?? '',
                $data['price'] ?? 0,
                $data['currency'] ?? 'RUB',
                $data['status'] ?? 'active'
            ]);
            return (int)$this->pdo->lastInsertId();
        }
    }

    public function update_event($id, $data) {
        if ($this->is_wp) {
            global $wpdb;
            $wpdb->update($this->prefix . 'events', $data, ['id' => $id]);
            return true;
        } else {
            $fields = [];
            $values = [];
            foreach ($data as $k => $v) {
                $fields[] = "$k = ?";
                $values[] = $v;
            }
            $values[] = (int)$id;
            $sql = "UPDATE events SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
        }
    }

    public function delete_event($id) {
        if ($this->is_wp) {
            global $wpdb;
            $wpdb->delete($this->prefix . 'events', ['id' => $id]);
            $wpdb->delete($this->prefix . 'slots', ['event_id' => $id]);
            $wpdb->delete($this->prefix . 'bookings', ['event_id' => $id]);
            $wpdb->delete($this->prefix . 'form_fields', ['event_id' => $id]);
        } else {
            $this->pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);
            $this->pdo->prepare("DELETE FROM slots WHERE event_id = ?")->execute([$id]);
            $this->pdo->prepare("DELETE FROM bookings WHERE event_id = ?")->execute([$id]);
            $this->pdo->prepare("DELETE FROM form_fields WHERE event_id = ?")->execute([$id]);
        }
        return true;
    }

    // Slots & Capacity
    public function get_slots_by_event($eventId) {
        if ($this->is_wp) {
            global $wpdb;
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->prefix}slots WHERE event_id = %d ORDER BY slot_date ASC, slot_time ASC",
                $eventId
            ), ARRAY_A);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM slots WHERE event_id = ? ORDER BY slot_date ASC, slot_time ASC");
            $stmt->execute([(int)$eventId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function get_available_months($eventId) {
        if ($this->is_wp) {
            global $wpdb;
            $sql = $wpdb->prepare("
                SELECT DISTINCT slot_year, slot_month
                FROM {$this->prefix}slots
                WHERE event_id = %d AND is_active = 1 AND booked_count < max_capacity
                ORDER BY slot_year ASC, slot_month ASC
            ", $eventId);
            return $wpdb->get_results($sql, ARRAY_A);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT slot_year, slot_month
                FROM slots
                WHERE event_id = ? AND is_active = 1 AND booked_count < max_capacity
                ORDER BY slot_year ASC, slot_month ASC
            ");
            $stmt->execute([(int)$eventId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function get_available_dates_for_month($eventId, $year, $month) {
        if ($this->is_wp) {
            global $wpdb;
            $sql = $wpdb->prepare("
                SELECT DISTINCT slot_date
                FROM {$this->prefix}slots
                WHERE event_id = %d AND slot_year = %d AND slot_month = %d AND is_active = 1 AND booked_count < max_capacity
                ORDER BY slot_date ASC
            ", $eventId, $year, $month);
            return $wpdb->get_col($sql);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT slot_date
                FROM slots
                WHERE event_id = ? AND slot_year = ? AND slot_month = ? AND is_active = 1 AND booked_count < max_capacity
                ORDER BY slot_date ASC
            ");
            $stmt->execute([(int)$eventId, (int)$year, (int)$month]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }

    public function get_available_times_for_date($eventId, $dateStr) {
        if ($this->is_wp) {
            global $wpdb;
            $sql = $wpdb->prepare("
                SELECT id, slot_time, max_capacity, booked_count, (max_capacity - booked_count) AS remaining_capacity
                FROM {$this->prefix}slots
                WHERE event_id = %d AND slot_date = %s AND is_active = 1 AND booked_count < max_capacity
                ORDER BY slot_time ASC
            ", $eventId, $dateStr);
            return $wpdb->get_results($sql, ARRAY_A);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT id, slot_time, max_capacity, booked_count, (max_capacity - booked_count) AS remaining_capacity
                FROM slots
                WHERE event_id = ? AND slot_date = ? AND is_active = 1 AND booked_count < max_capacity
                ORDER BY slot_time ASC
            ");
            $stmt->execute([(int)$eventId, $dateStr]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function get_slot($slotId) {
        if ($this->is_wp) {
            global $wpdb;
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->prefix}slots WHERE id = %d", $slotId), ARRAY_A);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM slots WHERE id = ?");
            $stmt->execute([(int)$slotId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    }

    public function insert_slot($data) {
        if ($this->is_wp) {
            global $wpdb;
            $wpdb->insert($this->prefix . 'slots', $data);
            return $wpdb->insert_id;
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO slots (event_id, slot_year, slot_month, slot_date, slot_time, max_capacity, booked_count, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['event_id'],
                $data['slot_year'],
                $data['slot_month'],
                $data['slot_date'],
                $data['slot_time'],
                $data['max_capacity'] ?? 10,
                $data['booked_count'] ?? 0,
                $data['is_active'] ?? 1
            ]);
            return (int)$this->pdo->lastInsertId();
        }
    }

    public function update_slot($slotId, $data) {
        if ($this->is_wp) {
            global $wpdb;
            $wpdb->update($this->prefix . 'slots', $data, ['id' => $slotId]);
            return true;
        } else {
            $fields = [];
            $values = [];
            foreach ($data as $k => $v) {
                $fields[] = "$k = ?";
                $values[] = $v;
            }
            $values[] = (int)$slotId;
            $stmt = $this->pdo->prepare("UPDATE slots SET " . implode(', ', $fields) . " WHERE id = ?");
            return $stmt->execute($values);
        }
    }

    public function delete_slot($slotId) {
        if ($this->is_wp) {
            global $wpdb;
            $wpdb->delete($this->prefix . 'slots', ['id' => $slotId]);
        } else {
            $this->pdo->prepare("DELETE FROM slots WHERE id = ?")->execute([(int)$slotId]);
        }
        return true;
    }

    public function increment_slot_booked($slotId, $count = 1) {
        if ($this->is_wp) {
            global $wpdb;
            return $wpdb->query($wpdb->prepare("UPDATE {$this->prefix}slots SET booked_count = booked_count + %d WHERE id = %d", $count, $slotId));
        } else {
            $stmt = $this->pdo->prepare("UPDATE slots SET booked_count = booked_count + ? WHERE id = ?");
            return $stmt->execute([(int)$count, (int)$slotId]);
        }
    }

    public function decrement_slot_booked($slotId, $count = 1) {
        if ($this->is_wp) {
            global $wpdb;
            return $wpdb->query($wpdb->prepare("UPDATE {$this->prefix}slots SET booked_count = MAX(0, booked_count - %d) WHERE id = %d", $count, $slotId));
        } else {
            $stmt = $this->pdo->prepare("UPDATE slots SET booked_count = MAX(0, booked_count - ?) WHERE id = ?");
            return $stmt->execute([(int)$count, (int)$slotId]);
        }
    }

    // Bookings
    public function insert_booking($data) {
        if ($this->is_wp) {
            global $wpdb;
            $wpdb->insert($this->prefix . 'bookings', $data);
            return $wpdb->insert_id;
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO bookings (event_id, slot_id, booking_code, client_name, client_email, client_phone, ticket_count, custom_fields, status, check_in, admin_notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['event_id'],
                $data['slot_id'],
                $data['booking_code'],
                $data['client_name'],
                $data['client_email'],
                $data['client_phone'],
                $data['ticket_count'] ?? 1,
                $data['custom_fields'] ?? '',
                $data['status'] ?? 'confirmed',
                $data['check_in'] ?? 0,
                $data['admin_notes'] ?? ''
            ]);
            return (int)$this->pdo->lastInsertId();
        }
    }

    public function get_bookings($filters = []) {
        $where = [];
        $params = [];

        if (!empty($filters['event_id'])) {
            $where[] = "b.event_id = ?";
            $params[] = (int)$filters['event_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = "b.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where[] = "(b.client_name LIKE ? OR b.client_email LIKE ? OR b.client_phone LIKE ? OR b.booking_code LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        if ($this->is_wp) {
            global $wpdb;
            // WP query format
            $query = "
                SELECT b.*, e.title AS event_title, s.slot_date, s.slot_time
                FROM {$this->prefix}bookings b
                LEFT JOIN {$this->prefix}events e ON b.event_id = e.id
                LEFT JOIN {$this->prefix}slots s ON b.slot_id = s.id
                $whereClause
                ORDER BY b.id DESC
            ";
            if (!empty($params)) {
                return $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
            }
            return $wpdb->get_results($query, ARRAY_A);
        } else {
            $query = "
                SELECT b.*, e.title AS event_title, s.slot_date, s.slot_time
                FROM bookings b
                LEFT JOIN events e ON b.event_id = e.id
                LEFT JOIN slots s ON b.slot_id = s.id
                $whereClause
                ORDER BY b.id DESC
            ";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function get_booking($id) {
        if ($this->is_wp) {
            global $wpdb;
            $sql = $wpdb->prepare("
                SELECT b.*, e.title AS event_title, s.slot_date, s.slot_time, e.location, e.price
                FROM {$this->prefix}bookings b
                LEFT JOIN {$this->prefix}events e ON b.event_id = e.id
                LEFT JOIN {$this->prefix}slots s ON b.slot_id = s.id
                WHERE b.id = %d
            ", $id);
            return $wpdb->get_row($sql, ARRAY_A);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT b.*, e.title AS event_title, s.slot_date, s.slot_time, e.location, e.price
                FROM bookings b
                LEFT JOIN events e ON b.event_id = e.id
                LEFT JOIN slots s ON b.slot_id = s.id
                WHERE b.id = ?
            ");
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    }

    public function get_booking_by_code($code) {
        if ($this->is_wp) {
            global $wpdb;
            $sql = $wpdb->prepare("
                SELECT b.*, e.title AS event_title, s.slot_date, s.slot_time, e.location, e.price
                FROM {$this->prefix}bookings b
                LEFT JOIN {$this->prefix}events e ON b.event_id = e.id
                LEFT JOIN {$this->prefix}slots s ON b.slot_id = s.id
                WHERE b.booking_code = %s
            ", $code);
            return $wpdb->get_row($sql, ARRAY_A);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT b.*, e.title AS event_title, s.slot_date, s.slot_time, e.location, e.price
                FROM bookings b
                LEFT JOIN events e ON b.event_id = e.id
                LEFT JOIN slots s ON b.slot_id = s.id
                WHERE b.booking_code = ?
            ");
            $stmt->execute([$code]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    }

    public function update_booking($id, $data) {
        if ($this->is_wp) {
            global $wpdb;
            $wpdb->update($this->prefix . 'bookings', $data, ['id' => $id]);
            return true;
        } else {
            $fields = [];
            $values = [];
            foreach ($data as $k => $v) {
                $fields[] = "$k = ?";
                $values[] = $v;
            }
            $values[] = (int)$id;
            $stmt = $this->pdo->prepare("UPDATE bookings SET " . implode(', ', $fields) . " WHERE id = ?");
            return $stmt->execute($values);
        }
    }

    public function delete_booking($id) {
        $booking = $this->get_booking($id);
        if ($booking && !empty($booking['slot_id'])) {
            $this->decrement_slot_booked($booking['slot_id'], $booking['ticket_count']);
        }

        if ($this->is_wp) {
            global $wpdb;
            $wpdb->delete($this->prefix . 'bookings', ['id' => $id]);
        } else {
            $this->pdo->prepare("DELETE FROM bookings WHERE id = ?")->execute([(int)$id]);
        }
        return true;
    }

    // Form Fields
    public function get_form_fields($eventId = 0) {
        if ($this->is_wp) {
            global $wpdb;
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->prefix}form_fields WHERE event_id = %d OR event_id = 0 ORDER BY sort_order ASC, id ASC",
                $eventId
            ), ARRAY_A);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT * FROM form_fields WHERE event_id = ? OR event_id = 0 ORDER BY sort_order ASC, id ASC
            ");
            $stmt->execute([(int)$eventId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function insert_form_field($data) {
        if ($this->is_wp) {
            global $wpdb;
            $wpdb->insert($this->prefix . 'form_fields', $data);
            return $wpdb->insert_id;
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO form_fields (event_id, field_key, field_label, field_type, field_options, placeholder, is_required, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['event_id'] ?? 0,
                $data['field_key'],
                $data['field_label'],
                $data['field_type'],
                $data['field_options'] ?? '',
                $data['placeholder'] ?? '',
                $data['is_required'] ?? 0,
                $data['sort_order'] ?? 0
            ]);
            return (int)$this->pdo->lastInsertId();
        }
    }

    public function delete_form_field($id) {
        if ($this->is_wp) {
            global $wpdb;
            $wpdb->delete($this->prefix . 'form_fields', ['id' => $id]);
        } else {
            $this->pdo->prepare("DELETE FROM form_fields WHERE id = ?")->execute([(int)$id]);
        }
        return true;
    }

    public function clear_and_save_form_fields($eventId, array $fields) {
        if ($this->is_wp) {
            global $wpdb;
            $wpdb->delete($this->prefix . 'form_fields', ['event_id' => $eventId]);
        } else {
            $this->pdo->prepare("DELETE FROM form_fields WHERE event_id = ?")->execute([(int)$eventId]);
        }

        foreach ($fields as $idx => $f) {
            $f['event_id'] = $eventId;
            $f['sort_order'] = $idx;
            $this->insert_form_field($f);
        }
        return true;
    }

    // Analytics Helper
    public function get_analytics() {
        if ($this->is_wp) {
            global $wpdb;
            $total_events = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->prefix}events");
            $total_slots = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->prefix}slots");
            $total_bookings = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->prefix}bookings");
            $confirmed_bookings = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->prefix}bookings WHERE status = 'confirmed'");
            $total_attendees = (int)$wpdb->get_var("SELECT SUM(ticket_count) FROM {$this->prefix}bookings WHERE status = 'confirmed'");
            $checkins = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->prefix}bookings WHERE check_in = 1");
        } else {
            $total_events = (int)$this->pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
            $total_slots = (int)$this->pdo->query("SELECT COUNT(*) FROM slots")->fetchColumn();
            $total_bookings = (int)$this->pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
            $confirmed_bookings = (int)$this->pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
            $total_attendees = (int)$this->pdo->query("SELECT COALESCE(SUM(ticket_count), 0) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
            $checkins = (int)$this->pdo->query("SELECT COUNT(*) FROM bookings WHERE check_in = 1")->fetchColumn();
        }

        return [
            'total_events' => $total_events,
            'total_slots' => $total_slots,
            'total_bookings' => $total_bookings,
            'confirmed_bookings' => $confirmed_bookings,
            'total_attendees' => $total_attendees,
            'checkins' => $checkins
        ];
    }
}
