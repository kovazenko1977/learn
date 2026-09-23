<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$qrs = $wpdb->get_results("SELECT q.*, m.full_name FROM {$wpdb->prefix}memorial_qr q LEFT JOIN {$wpdb->prefix}memorials m ON q.memorial_id = m.id ORDER BY q.id DESC", ARRAY_A);
?>
<div class="wrap mp-admin-wrap">
    <h1>Генерируемые QR-коды</h1>
    <div class="mp-card">
        <div class="mp-gallery-grid">
            <?php foreach ($qrs as $qr): ?>
                <div class="mp-gallery-item">
                    <img src="<?php echo esc_url($qr['qr_image_url']); ?>" style="object-fit: contain;" />
                    <div style="font-size: 11px; margin-top:5px;"><strong><?php echo esc_html($qr['full_name']); ?></strong></div>
                    <div style="font-size: 10px; color:#666;">Код: <?php echo esc_html($qr['code']); ?></div>
                    <a href="<?php echo esc_url($qr['qr_image_url']); ?>" download class="button button-small" style="margin-top:5px;">PNG</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
