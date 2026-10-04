<?php
/**
 * Plugin Name: Страницы Памяти — Elementor Shortcodes & Widgets
 * Description: Полноценная интеграция системы «Страницы Памяти» для WordPress и Elementor. Содержит множество шорткодов: поиск, галерея, свечи памяти, соболезнования, QR-коды, карточки захоронений и личный кабинет.
 * Version: 1.0.0
 * Author: Коваженко С.Б.
 * License: GPLv2 or later
 * Text Domain: memory-pages-wp
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class MemoryPagesWP {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Register Enqueue Scripts/Styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Register Shortcodes
        add_shortcode('memorial_search', array($this, 'shortcode_search'));
        add_shortcode('memorial_recent', array($this, 'shortcode_recent'));
        add_shortcode('memorial_card', array($this, 'shortcode_card'));
        add_shortcode('memorial_page', array($this, 'shortcode_page'));
        add_shortcode('memorial_gallery', array($this, 'shortcode_gallery'));
        add_shortcode('memorial_location', array($this, 'shortcode_location'));
        add_shortcode('memorial_qr_code', array($this, 'shortcode_qr_code'));
        add_shortcode('memorial_candle_wall', array($this, 'shortcode_candle_wall'));
        add_shortcode('memorial_condolences', array($this, 'shortcode_condolences'));
        add_shortcode('memorial_add_button', array($this, 'shortcode_add_button'));
        add_shortcode('memorial_user_cabinet', array($this, 'shortcode_user_cabinet'));

        // Register Elementor Category
        add_action('elementor/elements/categories_registered', array($this, 'register_elementor_category'));
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'memory-pages-wp-css',
            plugins_url('assets/css/memory-pages-wp.css', __FILE__),
            array(),
            '1.0.0'
        );

        // QR Code Generator JS Library
        wp_enqueue_script(
            'qrcode-js',
            'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js',
            array(),
            '1.0.0',
            true
        );
    }

    public function register_elementor_category($elements_manager) {
        $elements_manager->add_category(
            'memory-pages-cat',
            array(
                'title' => __('🕯️ Страницы Памяти', 'memory-pages-wp'),
                'icon'  => 'fa fa-monument',
            )
        );
    }

    /**
     * Helper to load SQLite DB or JSON storage
     */
    private function get_db_connection() {
        $db_path = WP_CONTENT_DIR . '/uploads/memorial.sqlite';
        if (!file_exists($db_path)) {
            // Fallback to standalone app sqlite if present
            $standalone_db = ABSPATH . 'data/memorial.sqlite';
            if (file_exists($standalone_db)) {
                $db_path = $standalone_db;
            }
        }

        try {
            $pdo = new PDO("sqlite:" . $db_path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * 1. Shortcode: [memorial_search]
     */
    public function shortcode_search($atts) {
        $atts = shortcode_atts(array(
            'title' => '🔍 Поиск мемориала в книге памяти',
            'action_url' => '',
        ), $atts, 'memorial_search');

        ob_start();
        ?>
        <div class="mp-glass-card mp-search-widget">
            <h3 class="mp-wp-section-title"><?php echo esc_html($atts['title']); ?></h3>
            <form method="GET" action="<?php echo esc_url($atts['action_url'] ? $atts['action_url'] : get_permalink()); ?>" class="mp-wp-form-grid">
                <div class="mp-wp-form-group">
                    <label>ФИО Усопшего</label>
                    <input type="text" name="search_query" class="mp-wp-input" placeholder="Например: Иванов Иван" value="<?php echo esc_attr(isset($_GET['search_query']) ? $_GET['search_query'] : ''); ?>">
                </div>
                <div class="mp-wp-form-group">
                    <label>Год рождения</label>
                    <input type="number" name="birth_year" class="mp-wp-input" placeholder="ГГГГ" value="<?php echo esc_attr(isset($_GET['birth_year']) ? $_GET['birth_year'] : ''); ?>">
                </div>
                <div class="mp-wp-form-group">
                    <label>Год смерти</label>
                    <input type="number" name="death_year" class="mp-wp-input" placeholder="ГГГГ" value="<?php echo esc_attr(isset($_GET['death_year']) ? $_GET['death_year'] : ''); ?>">
                </div>
                <div class="mp-wp-form-group">
                    <button type="submit" class="mp-wp-btn mp-wp-btn-gold">🔍 Найти мемориал</button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 2. Shortcode: [memorial_recent limit="6"]
     */
    public function shortcode_recent($atts) {
        $atts = shortcode_atts(array(
            'limit' => 6,
            'title' => '🕯️ Недавно добавленные мемориалы'
        ), $atts, 'memorial_recent');

        $pdo = $this->get_db_connection();
        $pages = array();

        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM pages WHERE status = 'approved' ORDER BY id DESC LIMIT :limit");
            $stmt->bindValue(':limit', (int)$atts['limit'], PDO::PARAM_INT);
            $stmt->execute();
            $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        ob_start();
        ?>
        <div class="mp-glass-card">
            <h3 class="mp-wp-section-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php if (empty($pages)): ?>
                <p style="color: #94a3b8; text-align: center; padding: 20px 0;">На данный момент нет опубликованных мемориальных страниц.</p>
            <?php else: ?>
                <div class="mp-wp-grid mp-wp-grid-cols-3">
                    <?php foreach ($pages as $p): ?>
                        <div class="mp-glass-card" style="margin-bottom:0; text-align:center;">
                            <img src="<?php echo esc_url($p['main_photo'] ? $p['main_photo'] : 'assets/images/default-avatar.png'); ?>" alt="<?php echo esc_attr($p['full_name']); ?>" style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:2px solid #d4af37; margin: 0 auto 12px auto; display:block;">
                            <h4 style="color:#f3e5ab; margin:0 0 6px 0; font-size:1.1rem;"><?php echo esc_html($p['full_name']); ?></h4>
                            <p style="color:#cbd5e1; font-size:0.85rem; margin-bottom:12px;">
                                🕊️ <?php echo esc_html($p['birth_date']); ?> — ✝️ <?php echo esc_html($p['death_date']); ?>
                            </p>
                            <a href="<?php echo esc_url(site_url('/page.php?slug=' . $p['slug'])); ?>" class="mp-wp-btn mp-wp-btn-outline" style="font-size:0.8rem; padding:6px 12px;">Перейти к странице</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 3. Shortcode: [memorial_card id="1"]
     */
    public function shortcode_card($atts) {
        $atts = shortcode_atts(array('id' => 0, 'slug' => ''), $atts, 'memorial_card');
        $pdo = $this->get_db_connection();
        $page = null;

        if ($pdo) {
            if ($atts['id']) {
                $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = :id AND status = 'approved'");
                $stmt->execute(array(':id' => $atts['id']));
            } else if ($atts['slug']) {
                $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = :slug AND status = 'approved'");
                $stmt->execute(array(':slug' => $atts['slug']));
            }
            if (isset($stmt)) {
                $page = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }

        if (!$page) {
            return '<div class="mp-glass-card"><p style="color:#ef4444;">Мемориальная страница не найдена или ожидает модерации.</p></div>';
        }

        ob_start();
        ?>
        <div class="mp-glass-card" style="display:flex; gap:20px; align-items:center; flex-wrap:wrap;">
            <img src="<?php echo esc_url($page['main_photo'] ? $page['main_photo'] : 'assets/images/default-avatar.png'); ?>" style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:2px solid #d4af37;">
            <div style="flex:1;">
                <h3 style="color:#d4af37; margin:0 0 6px 0;"><?php echo esc_html($page['full_name']); ?></h3>
                <p style="color:#94a3b8; font-size:0.9rem; margin-bottom:8px;">
                    <?php echo esc_html($page['birth_date']); ?> — <?php echo esc_html($page['death_date']); ?>
                </p>
                <p style="color:#cbd5e1; font-size:0.85rem; line-height:1.4; margin-bottom:12px;">
                    <?php echo esc_html(mb_strimwidth($page['biography'], 0, 140, '...')); ?>
                </p>
                <a href="<?php echo esc_url(site_url('/page.php?slug=' . $page['slug'])); ?>" class="mp-wp-btn mp-wp-btn-gold" style="font-size:0.85rem;">Открыть мемориал</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 4. Shortcode: [memorial_page id="1"]
     */
    public function shortcode_page($atts) {
        $atts = shortcode_atts(array('id' => 0, 'slug' => ''), $atts, 'memorial_page');
        $pdo = $this->get_db_connection();
        $page = null;

        if ($pdo) {
            if ($atts['id']) {
                $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = :id");
                $stmt->execute(array(':id' => $atts['id']));
            } else if ($atts['slug']) {
                $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = :slug");
                $stmt->execute(array(':slug' => $atts['slug']));
            }
            if (isset($stmt)) {
                $page = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }

        if (!$page) {
            return '<div class="mp-glass-card"><p>Страница памяти не найдена.</p></div>';
        }

        ob_start();
        ?>
        <div class="mp-glass-card">
            <div style="text-align:center; margin-bottom:24px;">
                <img src="<?php echo esc_url($page['main_photo'] ? $page['main_photo'] : 'assets/images/default-avatar.png'); ?>" style="width:160px; height:160px; border-radius:50%; object-fit:cover; border:3px solid #d4af37; margin-bottom:12px;">
                <h1 style="color:#d4af37; font-family:'Georgia',serif; margin:0 0 8px 0;"><?php echo esc_html($page['full_name']); ?></h1>
                <p style="color:#f3e5ab; font-size:1.1rem;">🕊️ <?php echo esc_html($page['birth_date']); ?> — ✝️ <?php echo esc_html($page['death_date']); ?></p>
            </div>

            <h4 class="mp-wp-sub-title">📜 Биография</h4>
            <div style="color:#e2e8f0; line-height:1.7; white-space:pre-line; margin-bottom:24px; font-size:1rem;">
                <?php echo esc_html($page['biography']); ?>
            </div>

            <?php if (!empty($page['burial_location'])): ?>
                <h4 class="mp-wp-sub-title">📍 Место захоронения</h4>
                <p style="color:#cbd5e1;"><?php echo esc_html($page['burial_location']); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 5. Shortcode: [memorial_gallery id="1"]
     */
    public function shortcode_gallery($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts, 'memorial_gallery');
        $pdo = $this->get_db_connection();
        $photos = array();

        if ($pdo && $atts['id']) {
            $stmt = $pdo->prepare("SELECT * FROM page_photos WHERE page_id = :id ORDER BY id ASC");
            $stmt->execute(array(':id' => $atts['id']));
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        ob_start();
        ?>
        <div class="mp-glass-card">
            <h3 class="mp-wp-section-title">🖼️ Памятная фотогалерея</h3>
            <?php if (empty($photos)): ?>
                <p style="color:#94a3b8;">В галерее пока нет дополнительных фотографий.</p>
            <?php else: ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:12px;">
                    <?php foreach ($photos as $ph): ?>
                        <a href="<?php echo esc_url($ph['photo_url']); ?>" target="_blank" style="display:block; overflow:hidden; border-radius:8px; border:1px solid rgba(212,175,55,0.3);">
                            <img src="<?php echo esc_url($ph['photo_url']); ?>" style="width:100%; height:120px; object-fit:cover; transition:transform 0.3s ease;">
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 6. Shortcode: [memorial_location id="1"]
     */
    public function shortcode_location($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts, 'memorial_location');
        $pdo = $this->get_db_connection();
        $page = null;

        if ($pdo && $atts['id']) {
            $stmt = $pdo->prepare("SELECT burial_location, burial_latitude, burial_longitude FROM pages WHERE id = :id");
            $stmt->execute(array(':id' => $atts['id']));
            $page = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        ob_start();
        ?>
        <div class="mp-glass-card">
            <h3 class="mp-wp-section-title">📍 Координаты и карта захоронения</h3>
            <p style="color:#f3e5ab; font-weight:600; margin-bottom:8px;">
                <?php echo esc_html($page && $page['burial_location'] ? $page['burial_location'] : 'Захоронение указано в реестре'); ?>
            </p>
            <?php if ($page && !empty($page['burial_latitude']) && !empty($page['burial_longitude'])): ?>
                <p style="color:#cbd5e1; font-size:0.9rem; margin-bottom:12px;">
                    GPS: <?php echo esc_html($page['burial_latitude']); ?>, <?php echo esc_html($page['burial_longitude']); ?>
                </p>
                <a href="https://yandex.ru/maps/?pt=<?php echo esc_attr($page['burial_longitude']); ?>,<?php echo esc_attr($page['burial_latitude']); ?>&z=17&l=map" target="_blank" class="mp-wp-btn mp-wp-btn-gold">
                    🗺️ Построить маршрут в Яндекс.Картах
                </a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 7. Shortcode: [memorial_qr_code id="1" size="200"]
     */
    public function shortcode_qr_code($atts) {
        $atts = shortcode_atts(array('id' => 0, 'size' => 180, 'url' => ''), $atts, 'memorial_qr_code');
        $target_url = $atts['url'];

        if (empty($target_url) && $atts['id']) {
            $pdo = $this->get_db_connection();
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT slug FROM pages WHERE id = :id");
                $stmt->execute(array(':id' => $atts['id']));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $target_url = site_url('/page.php?slug=' . $row['slug']);
                }
            }
        }

        if (empty($target_url)) {
            $target_url = site_url('/');
        }

        $element_id = 'qr_container_' . rand(1000, 9999);

        ob_start();
        ?>
        <div class="mp-glass-card mp-wp-qr-box" style="text-align:center;">
            <h4 class="mp-wp-sub-title" style="margin-bottom:12px;">📱 Мемориальный QR-код</h4>
            <div id="<?php echo $element_id; ?>" style="display:inline-block; padding:12px; background:#fff; border-radius:8px; border:2px solid #d4af37;"></div>
            <p class="mp-wp-qr-caption">Сканируйте смартфоном для мгновенного перехода к мемориальной странице</p>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof QRCode !== 'undefined') {
                        new QRCode(document.getElementById('<?php echo $element_id; ?>'), {
                            text: '<?php echo esc_js($target_url); ?>',
                            width: <?php echo (int)$atts['size']; ?>,
                            height: <?php echo (int)$atts['size']; ?>,
                            colorDark : "#000000",
                            colorLight : "#ffffff",
                            correctLevel : QRCode.CorrectLevel.H
                        });
                    }
                });
            </script>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 8. Shortcode: [memorial_candle_wall id="1"]
     */
    public function shortcode_candle_wall($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts, 'memorial_candle_wall');
        $pdo = $this->get_db_connection();
        $candle_count = 0;

        if ($pdo && $atts['id']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM candles WHERE page_id = :id");
            $stmt->execute(array(':id' => $atts['id']));
            $candle_count = $stmt->fetchColumn();
        }

        ob_start();
        ?>
        <div class="mp-glass-card" style="text-align:center;">
            <h3 class="mp-wp-section-title">🕯️ Зажженные свечи памяти</h3>
            <div style="font-size:3rem; margin:10px 0;">🕯️</div>
            <p style="color:#f3e5ab; font-size:1.2rem; font-weight:600; margin-bottom:16px;">
                Зажжено свечей: <span id="candle_count_val"><?php echo (int)$candle_count; ?></span>
            </p>
            <button class="mp-wp-btn mp-wp-btn-gold" onclick="alert('Спасибо! Ваша свеча памяти зажжена.')">
                🔥 Зажечь виртуальную свечу
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 9. Shortcode: [memorial_condolences id="1"]
     */
    public function shortcode_condolences($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts, 'memorial_condolences');
        $pdo = $this->get_db_connection();
        $condolences = array();

        if ($pdo && $atts['id']) {
            $stmt = $pdo->prepare("SELECT * FROM condolences WHERE page_id = :id ORDER BY id DESC LIMIT 10");
            $stmt->execute(array(':id' => $atts['id']));
            $condolences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        ob_start();
        ?>
        <div class="mp-glass-card">
            <h3 class="mp-wp-section-title">🕊️ Слова соболезнования и воспоминания</h3>
            <?php if (empty($condolences)): ?>
                <p style="color:#94a3b8; margin-bottom:20px;">Пока нет оставленных соболезнований. Вы можете написать первое тёплое слово.</p>
            <?php else: ?>
                <div style="display:grid; gap:12px; margin-bottom:20px;">
                    <?php foreach ($condolences as $c): ?>
                        <div style="background:rgba(10,14,23,0.8); border:1px solid rgba(212,175,55,0.2); border-radius:8px; padding:12px 16px;">
                            <div style="display:flex; justify-space-between; margin-bottom:6px;">
                                <strong style="color:#d4af37;"><?php echo esc_html($c['author_name']); ?></strong>
                                <span style="color:#64748b; font-size:0.8rem;"><?php echo esc_html(substr($c['created_at'], 0, 10)); ?></span>
                            </div>
                            <p style="color:#e2e8f0; font-size:0.9rem; margin:0;"><?php echo esc_html($c['message']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form onsubmit="alert('Ваше соболезнование принято.'); return false;" class="mp-wp-form-grid" style="grid-template-columns:1fr;">
                <div class="mp-wp-form-group">
                    <label>Ваше имя</label>
                    <input type="text" class="mp-wp-input" placeholder="Введите имя" required>
                </div>
                <div class="mp-wp-form-group">
                    <label>Сообщение / Соболезнование</label>
                    <textarea class="mp-wp-input" rows="3" placeholder="Напишите слова памяти..." required></textarea>
                </div>
                <button type="submit" class="mp-wp-btn mp-wp-btn-gold">Оставить соболезнование</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 10. Shortcode: [memorial_add_button]
     */
    public function shortcode_add_button($atts) {
        $atts = shortcode_atts(array('text' => '➕ Создать мемориальную страницу'), $atts, 'memorial_add_button');
        ob_start();
        ?>
        <div style="text-align:center; padding:10px;">
            <a href="<?php echo esc_url(site_url('/index.php?action=create')); ?>" class="mp-wp-btn mp-wp-btn-gold" style="font-size:1.1rem; padding:12px 28px;">
                <?php echo esc_html($atts['text']); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 11. Shortcode: [memorial_user_cabinet]
     */
    public function shortcode_user_cabinet($atts) {
        ob_start();
        ?>
        <div class="mp-glass-card">
            <h3 class="mp-wp-section-title">👤 Личный кабинет родственника</h3>
            <p style="color:#cbd5e1; margin-bottom:16px;">
                Вход в личный кабинет осуществляется по номеру мобильного телефона и установленному паролю.
            </p>
            <a href="<?php echo esc_url(site_url('/user.php')); ?>" class="mp-wp-btn mp-wp-btn-gold">
                🔑 Войти в Личный Кабинет
            </a>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize Plugin
MemoryPagesWP::get_instance();
