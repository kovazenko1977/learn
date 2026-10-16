<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$slug = get_query_var('memorial_slug');

$table_pages = $wpdb->prefix . 'memorial_pages';
$page = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_pages WHERE slug = %s AND status = 'approved'", $slug));

if (!$page) {
    status_header(404);
    get_header();
    echo '<div style="max-width:800px; margin:80px auto; text-align:center; color:#fff;"><h1>404 — Мемориальная страница не найдена</h1><p>Запрашиваемая страница усопшего не существует или находится на модерации.</p><a href="' . home_url('/') . '" style="color:#d4af37;">Вернуться на главную</a></div>';
    get_footer();
    exit;
}

// Fetch photos, condolences, candles
$table_photos = $wpdb->prefix . 'memorial_photos';
$table_condolences = $wpdb->prefix . 'memorial_condolences';
$table_candles = $wpdb->prefix . 'memorial_candles';

$photos = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_photos WHERE page_id = %d ORDER BY id ASC", $page->id));
$condolences = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_condolences WHERE page_id = %d ORDER BY id DESC", $page->id));
$candle_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_candles WHERE page_id = %d", $page->id));

get_header();
?>

<div class="mp-public-wrap" style="max-width:1000px; margin:40px auto;">
    <!-- Main Memorial Hero -->
    <div class="mp-card" style="text-align:center;">
        <img src="<?php echo esc_url($page->main_photo ? $page->main_photo : MEMORY_PAGES_URL . 'assets/images/default-avatar.png'); ?>" style="width:180px; height:180px; border-radius:50%; object-fit:cover; border:3px solid #d4af37; margin-bottom:16px;">
        <h1 style="color:#d4af37; font-family:'Georgia',serif; font-size:2.2rem; margin:0 0 10px 0;"><?php echo esc_html($page->full_name); ?></h1>
        <p style="color:#f3e5ab; font-size:1.2rem; margin-bottom:20px;">
            🕊️ <?php echo esc_html($page->birth_date); ?> — ✝️ <?php echo esc_html($page->death_date); ?>
        </p>

        <!-- Candle Lighting Widget -->
        <div style="background:rgba(10,14,23,0.8); border:1px solid rgba(212,175,55,0.3); border-radius:12px; padding:16px; display:inline-block; margin-bottom:20px;">
            <div style="font-size:2.5rem; margin-bottom:6px;">🕯️</div>
            <p style="color:#f3e5ab; font-size:1.1rem; margin:0 0 12px 0;">
                Зажжено свечей памяти: <strong class="mp-candle-count-num" style="color:#fff;"><?php echo intval($candle_count); ?></strong>
            </p>
            <button class="mp-btn mp-btn-gold mp-light-candle-btn" data-page-id="<?php echo $page->id; ?>">
                🔥 Зажечь виртуальную свечу
            </button>
        </div>
    </div>

    <!-- Biography Section -->
    <div class="mp-card">
        <h3 class="mp-title">📜 Биография и память</h3>
        <div style="color:#e2e8f0; line-height:1.8; font-size:1.05rem; white-space:pre-line;">
            <?php echo esc_html($page->biography); ?>
        </div>
    </div>

    <!-- Multi-Photo Gallery -->
    <?php if (!empty($photos)): ?>
        <div class="mp-card">
            <h3 class="mp-title">🖼️ Памятная фотогалерея</h3>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:14px;">
                <?php foreach ($photos as $ph): ?>
                    <a href="<?php echo esc_url($ph->photo_url); ?>" target="_blank" style="display:block; border-radius:8px; overflow:hidden; border:1px solid #d4af37;">
                        <img src="<?php echo esc_url($ph->photo_url); ?>" style="width:100%; height:130px; object-fit:cover;">
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Location & QR Code -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
        <div class="mp-card">
            <h3 class="mp-title">📍 Место захоронения</h3>
            <p style="color:#f3e5ab; font-weight:600; font-size:1.1rem; margin-bottom:10px;">
                <?php echo esc_html($page->burial_location ? $page->burial_location : 'Указано в Книге Памяти'); ?>
            </p>
            <?php if (!empty($page->burial_latitude) && !empty($page->burial_longitude)): ?>
                <p style="color:#cbd5e1; font-size:0.9rem; margin-bottom:14px;">
                    GPS: <?php echo esc_html($page->burial_latitude); ?>, <?php echo esc_html($page->burial_longitude); ?>
                </p>
                <a href="https://yandex.ru/maps/?pt=<?php echo esc_attr($page->burial_longitude); ?>,<?php echo esc_attr($page->burial_latitude); ?>&z=17&l=map" target="_blank" class="mp-btn mp-btn-gold">
                    🗺️ Построить маршрут в Яндекс.Картах
                </a>
            <?php endif; ?>
        </div>

        <div class="mp-card" style="text-align:center;">
            <h3 class="mp-title">📱 QR-код мемориала</h3>
            <div id="qr_code_holder" style="display:inline-block; padding:12px; background:#fff; border-radius:8px; border:2px solid #d4af37; margin-bottom:10px;"></div>
            <p style="color:#94a3b8; font-size:0.85rem; margin:0;">Постоянный QR-код для установки на ритуальную табличку</p>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof QRCode !== 'undefined') {
                        new QRCode(document.getElementById('qr_code_holder'), {
                            text: '<?php echo esc_js(home_url('/memory/' . $page->slug . '/')); ?>',
                            width: 160,
                            height: 160
                        });
                    }
                });
            </script>
        </div>
    </div>

    <!-- Condolences Wall -->
    <div class="mp-card">
        <h3 class="mp-title">🕊️ Слова соболезнования</h3>
        <?php if (empty($condolences)): ?>
            <p style="color:#94a3b8;">Пока никто не оставил соболезнований. Вы можете написать первые теплые слова.</p>
        <?php else: ?>
            <div style="display:grid; gap:12px; margin-bottom:24px;">
                <?php foreach ($condolences as $c): ?>
                    <div style="background:rgba(10,14,23,0.8); border:1px solid rgba(212,175,55,0.2); border-radius:8px; padding:14px;">
                        <strong style="color:#d4af37;"><?php echo esc_html($c->author_name); ?></strong>
                        <span style="color:#64748b; font-size:0.8rem; float:right;"><?php echo esc_html(substr($c->created_at, 0, 10)); ?></span>
                        <p style="color:#e2e8f0; font-size:0.95rem; margin:8px 0 0 0;"><?php echo esc_html($c->message); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- New Condolence Form -->
        <form class="mp-condolence-form">
            <input type="hidden" name="page_id" value="<?php echo $page->id; ?>">
            <div class="mp-form-group">
                <label>Ваше имя</label>
                <input type="text" name="author_name" class="mp-input" placeholder="Введите ваше имя" required>
            </div>
            <div class="mp-form-group">
                <label>Ваше соболезнование или воспоминание</label>
                <textarea name="message" class="mp-input" rows="3" placeholder="Напишите слова памяти..." required></textarea>
            </div>
            <button type="submit" class="mp-btn mp-btn-gold">Оставить соболезнование</button>
        </form>
    </div>
</div>

<?php
get_footer();
