<?php
if (!defined('ABSPATH')) {
    exit;
}

class MemoryPagesElementorWidgets {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
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

    public function register_elementor_category($elements_manager) {
        $elements_manager->add_category(
            'memory-pages-cat',
            array(
                'title' => __('🕯️ Страницы Памяти', 'memory-pages'),
                'icon'  => 'fa fa-monument',
            )
        );
    }

    // 1. [memorial_search]
    public function shortcode_search($atts) {
        $atts = shortcode_atts(array('title' => '🔍 Поиск мемориала в Книге Памяти'), $atts);
        ob_start();
        ?>
        <div class="mp-card">
            <h3 class="mp-title"><?php echo esc_html($atts['title']); ?></h3>
            <form method="GET" action="<?php echo esc_url(home_url('/')); ?>" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; align-items:end;">
                <div class="mp-form-group">
                    <label>ФИО Усопшего</label>
                    <input type="text" name="search_fio" class="mp-input" placeholder="Иванов Иван">
                </div>
                <div class="mp-form-group">
                    <label>Год рождения</label>
                    <input type="number" name="birth_year" class="mp-input" placeholder="ГГГГ">
                </div>
                <div class="mp-form-group">
                    <label>Год смерти</label>
                    <input type="number" name="death_year" class="mp-input" placeholder="ГГГГ">
                </div>
                <div class="mp-form-group">
                    <button type="submit" class="mp-btn mp-btn-gold">🔍 Найти мемориал</button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    // 2. [memorial_recent limit="6"]
    public function shortcode_recent($atts) {
        $atts = shortcode_atts(array('limit' => 6, 'title' => '🕯️ Недавно добавленные мемориалы'), $atts);
        global $wpdb;
        $table_pages = $wpdb->prefix . 'memorial_pages';
        $pages = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pages WHERE status = 'approved' ORDER BY id DESC LIMIT %d", intval($atts['limit'])));

        ob_start();
        ?>
        <div class="mp-card">
            <h3 class="mp-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php if (empty($pages)): ?>
                <p style="color:#94a3b8;">Пока нет опубликованных страниц памяти.</p>
            <?php else: ?>
                <div class="mp-grid-3">
                    <?php foreach ($pages as $p): ?>
                        <div class="mp-card" style="margin-bottom:0; text-align:center;">
                            <img src="<?php echo esc_url($p->main_photo ? $p->main_photo : MEMORY_PAGES_URL . 'assets/images/default-avatar.png'); ?>" style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:2px solid #d4af37; margin-bottom:10px;">
                            <h4 style="color:#f3e5ab; margin:0 0 6px 0;"><?php echo esc_html($p->full_name); ?></h4>
                            <p style="color:#cbd5e1; font-size:0.85rem; margin-bottom:12px;">🕊️ <?php echo esc_html($p->birth_date); ?> — ✝️ <?php echo esc_html($p->death_date); ?></p>
                            <a href="<?php echo esc_url(home_url('/memory/' . $p->slug . '/')); ?>" class="mp-btn mp-btn-gold" style="font-size:0.8rem; padding:6px 12px;">Перейти к странице</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // 3. [memorial_card id="1"]
    public function shortcode_card($atts) {
        $atts = shortcode_atts(array('id' => 0, 'slug' => ''), $atts);
        global $wpdb;
        $table_pages = $wpdb->prefix . 'memorial_pages';

        if ($atts['id']) {
            $page = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_pages WHERE id = %d AND status = 'approved'", $atts['id']));
        } else {
            $page = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_pages WHERE slug = %s AND status = 'approved'", $atts['slug']));
        }

        if (!$page) return '<div class="mp-card"><p>Мемориал не найден.</p></div>';

        ob_start();
        ?>
        <div class="mp-card" style="display:flex; gap:20px; align-items:center; flex-wrap:wrap;">
            <img src="<?php echo esc_url($page->main_photo ? $page->main_photo : MEMORY_PAGES_URL . 'assets/images/default-avatar.png'); ?>" style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:2px solid #d4af37;">
            <div>
                <h3 style="color:#d4af37; margin:0 0 6px 0;"><?php echo esc_html($page->full_name); ?></h3>
                <p style="color:#94a3b8; font-size:0.9rem; margin-bottom:8px;"><?php echo esc_html($page->birth_date); ?> — <?php echo esc_html($page->death_date); ?></p>
                <a href="<?php echo esc_url(home_url('/memory/' . $page->slug . '/')); ?>" class="mp-btn mp-btn-gold">Открыть мемориал</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // 4. [memorial_page id="1"]
    public function shortcode_page($atts) {
        $atts = shortcode_atts(array('id' => 0, 'slug' => ''), $atts);
        global $wpdb;
        $table_pages = $wpdb->prefix . 'memorial_pages';

        if ($atts['id']) {
            $page = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_pages WHERE id = %d", $atts['id']));
        } else {
            $page = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_pages WHERE slug = %s", $atts['slug']));
        }

        if (!$page) return '<div class="mp-card"><p>Мемориал не найден.</p></div>';

        ob_start();
        ?>
        <div class="mp-card" style="text-align:center;">
            <img src="<?php echo esc_url($page->main_photo ? $page->main_photo : MEMORY_PAGES_URL . 'assets/images/default-avatar.png'); ?>" style="width:160px; height:160px; border-radius:50%; object-fit:cover; border:3px solid #d4af37; margin-bottom:12px;">
            <h2 style="color:#d4af37; margin:0 0 8px 0;"><?php echo esc_html($page->full_name); ?></h2>
            <p style="color:#f3e5ab; font-size:1.1rem; margin-bottom:16px;">🕊️ <?php echo esc_html($page->birth_date); ?> — ✝️ <?php echo esc_html($page->death_date); ?></p>
            <div style="color:#e2e8f0; line-height:1.7; text-align:left; white-space:pre-line;"><?php echo esc_html($page->biography); ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    // 5. [memorial_gallery id="1"]
    public function shortcode_gallery($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $table_photos = $wpdb->prefix . 'memorial_photos';
        $photos = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_photos WHERE page_id = %d ORDER BY id ASC", $atts['id']));

        ob_start();
        ?>
        <div class="mp-card">
            <h3 class="mp-title">🖼️ Памятная фотогалерея</h3>
            <?php if (empty($photos)): ?>
                <p style="color:#94a3b8;">В галерее пока нет дополнительних фото.</p>
            <?php else: ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(130px, 1fr)); gap:12px;">
                    <?php foreach ($photos as $ph): ?>
                        <a href="<?php echo esc_url($ph->photo_url); ?>" target="_blank"><img src="<?php echo esc_url($ph->photo_url); ?>" style="width:100%; height:110px; object-fit:cover; border-radius:6px; border:1px solid #d4af37;"></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // 6. [memorial_location id="1"]
    public function shortcode_location($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $table_pages = $wpdb->prefix . 'memorial_pages';
        $page = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_pages WHERE id = %d", $atts['id']));

        ob_start();
        ?>
        <div class="mp-card">
            <h3 class="mp-title">📍 Место захоронения</h3>
            <p style="color:#f3e5ab; font-weight:600; margin-bottom:10px;"><?php echo esc_html($page ? $page->burial_location : 'Указано в реестре'); ?></p>
            <?php if ($page && !empty($page->burial_latitude) && !empty($page->burial_longitude)): ?>
                <a href="https://yandex.ru/maps/?pt=<?php echo esc_attr($page->burial_longitude); ?>,<?php echo esc_attr($page->burial_latitude); ?>&z=17&l=map" target="_blank" class="mp-btn mp-btn-gold">🗺️ Маршрут в Яндекс.Картах</a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // 7. [memorial_qr_code id="1" size="180"]
    public function shortcode_qr_code($atts) {
        $atts = shortcode_atts(array('id' => 0, 'size' => 180, 'url' => ''), $atts);
        $url = $atts['url'];

        if (empty($url) && $atts['id']) {
            global $wpdb;
            $table_pages = $wpdb->prefix . 'memorial_pages';
            $slug = $wpdb->get_var($wpdb->prepare("SELECT slug FROM $table_pages WHERE id = %d", $atts['id']));
            if ($slug) {
                $url = home_url('/memory/' . $slug . '/');
            }
        }

        if (empty($url)) $url = home_url('/');

        $el_id = 'qr_element_' . rand(1000, 9999);
        ob_start();
        ?>
        <div class="mp-card" style="text-align:center;">
            <h4 style="color:#d4af37; margin-bottom:12px;">📱 Мемориальный QR-код</h4>
            <div id="<?php echo $el_id; ?>" style="display:inline-block; padding:10px; background:#fff; border-radius:8px; border:2px solid #d4af37;"></div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof QRCode !== 'undefined') {
                        new QRCode(document.getElementById('<?php echo $el_id; ?>'), {
                            text: '<?php echo esc_js($url); ?>',
                            width: <?php echo intval($atts['size']); ?>,
                            height: <?php echo intval($atts['size']); ?>
                        });
                    }
                });
            </script>
        </div>
        <?php
        return ob_get_clean();
    }

    // 8. [memorial_candle_wall id="1"]
    public function shortcode_candle_wall($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $table_candles = $wpdb->prefix . 'memorial_candles';
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_candles WHERE page_id = %d", $atts['id']));

        ob_start();
        ?>
        <div class="mp-card" style="text-align:center;">
            <h3 class="mp-title">🕯️ Свечи памяти</h3>
            <div style="font-size:2.5rem; margin-bottom:8px;">🕯️</div>
            <p style="color:#f3e5ab; font-size:1.1rem; margin-bottom:14px;">Зажжено свечей: <strong class="mp-candle-count-num"><?php echo intval($count); ?></strong></p>
            <button class="mp-btn mp-btn-gold mp-light-candle-btn" data-page-id="<?php echo intval($atts['id']); ?>">🔥 Зажечь свечу памяти</button>
        </div>
        <?php
        return ob_get_clean();
    }

    // 9. [memorial_condolences id="1"]
    public function shortcode_condolences($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $table_condolences = $wpdb->prefix . 'memorial_condolences';
        $condolences = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_condolences WHERE page_id = %d ORDER BY id DESC LIMIT 10", $atts['id']));

        ob_start();
        ?>
        <div class="mp-card">
            <h3 class="mp-title">🕊️ Соболезнования</h3>
            <?php foreach ($condolences as $c): ?>
                <div style="background:rgba(10,14,23,0.8); border:1px solid rgba(212,175,55,0.2); border-radius:8px; padding:12px; margin-bottom:10px;">
                    <strong style="color:#d4af37;"><?php echo esc_html($c->author_name); ?></strong>
                    <p style="color:#e2e8f0; font-size:0.9rem; margin:6px 0 0 0;"><?php echo esc_html($c->message); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // 10. [memorial_add_button]
    public function shortcode_add_button($atts) {
        $atts = shortcode_atts(array('text' => '➕ Создать мемориальную страницу'), $atts);
        ob_start();
        ?>
        <div style="text-align:center; padding:10px;">
            <a href="<?php echo esc_url(home_url('/#create-memorial')); ?>" class="mp-btn mp-btn-gold" style="font-size:1.1rem; padding:12px 28px;"><?php echo esc_html($atts['text']); ?></a>
        </div>
        <?php
        return ob_get_clean();
    }

    // 11. [memorial_user_cabinet]
    public function shortcode_user_cabinet($atts) {
        ob_start();
        ?>
        <div class="mp-card">
            <h3 class="mp-title">👤 Личный кабинет родственника</h3>
            <p style="color:#cbd5e1; margin-bottom:14px;">Авторизация и управление созданным мемориалом по номеру телефона.</p>
            <a href="<?php echo esc_url(home_url('/user.php')); ?>" class="mp-btn mp-btn-gold">🔑 Войти в Личный Кабинет</a>
        </div>
        <?php
        return ob_get_clean();
    }
}

MemoryPagesElementorWidgets::get_instance();
