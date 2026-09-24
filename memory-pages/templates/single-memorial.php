<?php
if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($memorial['full_name']); ?> — Страница памяти</title>
    <?php wp_head(); ?>
</head>
<body class="mp-public-body">

<div class="mp-public-container">

    <!-- Header Card -->
    <div class="mp-header-card">
        <?php if (!empty($memorial['main_photo_url'])): ?>
            <div class="mp-photo-frame">
                <img src="<?php echo esc_url($memorial['main_photo_url']); ?>" alt="<?php echo esc_attr($memorial['full_name']); ?>" class="mp-main-photo" />
                <span class="mp-ribbon">ПАМЯТЬ</span>
            </div>
        <?php endif; ?>

        <h1 class="mp-full-name"><?php echo esc_html($memorial['full_name']); ?></h1>

        <div class="mp-dates">
            <?php
            $birth = $memorial['birth_date'] ? date('d.m.Y', strtotime($memorial['birth_date'])) : '...';
            $death = $memorial['death_date'] ? date('d.m.Y', strtotime($memorial['death_date'])) : '...';
            echo esc_html($birth . ' — ' . $death);
            ?>
        </div>

        <?php if (!empty($memorial['birth_place'])): ?>
            <div class="mp-birth-place">📍 Место рождения: <?php echo esc_html($memorial['birth_place']); ?></div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="mp-actions-bar">
            <button class="mp-btn mp-btn-flower" id="mp-public-flower-btn" data-id="<?php echo esc_attr($memorial['id']); ?>">
                🌸 Возложить цветок (<span id="mp-flower-count"><?php echo esc_html($stats_totals['flowers'] ?? 0); ?></span>)
            </button>
            <button class="mp-btn mp-btn-candle" id="mp-public-candle-btn" data-id="<?php echo esc_attr($memorial['id']); ?>">
                🕯 Зажечь свечу (<span id="mp-candle-count"><?php echo esc_html($stats_totals['candles'] ?? 0); ?></span>)
            </button>
            <button class="mp-btn mp-btn-share" id="mp-public-share-btn" data-id="<?php echo esc_attr($memorial['id']); ?>">
                🔗 Поделиться
            </button>
        </div>
    </div>

    <!-- Biography Section -->
    <?php if (!empty($memorial['biography'])): ?>
    <div class="mp-section">
        <h2 class="mp-section-title">📜 Биография</h2>
        <div class="mp-biography-content">
            <?php echo wp_kses_post(wpautop($memorial['biography'])); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Burial Location -->
    <?php if (!empty($memorial['cemetery'])): ?>
    <div class="mp-section">
        <h2 class="mp-section-title">🕯 Место захоронения</h2>
        <div class="mp-burial-grid">
            <div class="mp-burial-item">
                <strong>Кладбище</strong>
                <span><?php echo esc_html($memorial['cemetery']); ?></span>
            </div>
            <?php if ($memorial['plot']): ?>
            <div class="mp-burial-item">
                <strong>Участок</strong>
                <span><?php echo esc_html($memorial['plot']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($memorial['row_number']): ?>
            <div class="mp-burial-item">
                <strong>Ряд</strong>
                <span><?php echo esc_html($memorial['row_number']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($memorial['place_number']): ?>
            <div class="mp-burial-item">
                <strong>Место</strong>
                <span><?php echo esc_html($memorial['place_number']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Gallery Section -->
    <?php if (!empty($photos)): ?>
    <div class="mp-section">
        <h2 class="mp-section-title">🖼 Галерея памяти</h2>
        <div class="mp-gallery-grid">
            <?php foreach ($photos as $photo): ?>
                <div class="mp-gallery-card">
                    <img src="<?php echo esc_url($photo['photo_url']); ?>" alt="<?php echo esc_attr($photo['caption']); ?>" />
                    <?php if (!empty($photo['caption'])): ?>
                        <div class="mp-gallery-caption"><?php echo esc_html($photo['caption']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Relatives & Contacts -->
    <?php if (!empty($relatives)): ?>
    <div class="mp-section">
        <h2 class="mp-section-title">👥 Родственники и близкие</h2>
        <button class="mp-btn mp-btn-share" id="mp-toggle-contacts-btn" data-id="<?php echo esc_attr($memorial['id']); ?>">📞 Показать контакты родственников</button>
        <div class="mp-contacts-hidden-content" style="display: none; margin-top: 15px;">
            <?php foreach ($relatives as $rel): ?>
                <div class="mp-contact-box">
                    <strong><?php echo esc_html($rel['full_name']); ?></strong> (<?php echo esc_html($rel['kinship_degree']); ?>)<br>
                    <?php if ($rel['show_contact_to_visitor']): ?>
                        <?php if ($rel['phone']): ?>📞 Тел: <?php echo esc_html($rel['phone']); ?><br><?php endif; ?>
                        <?php if ($rel['email']): ?>✉️ Email: <?php echo esc_html($rel['email']); ?><br><?php endif; ?>
                        <?php if ($rel['telegram']): ?>💬 Telegram: <?php echo esc_html($rel['telegram']); ?><br><?php endif; ?>
                    <?php else: ?>
                        <em>(Контакты скрыты по желанию родственника)</em>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php wp_footer(); ?>
</body>
</html>
