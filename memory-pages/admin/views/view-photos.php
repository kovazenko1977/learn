<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$photos = $wpdb->get_results("SELECT p.*, m.full_name, m.code FROM {$wpdb->prefix}memorial_photos p LEFT JOIN {$wpdb->prefix}memorials m ON p.memorial_id = m.id ORDER BY p.id DESC", ARRAY_A);
?>
<div class="wrap mp-admin-wrap">
    <h1>Управление фотографиями галерей</h1>
    <div class="mp-card">
        <div class="mp-gallery-grid">
            <?php foreach ($photos as $photo): ?>
                <div class="mp-gallery-item">
                    <img src="<?php echo esc_url($photo['photo_url']); ?>" />
                    <div style="font-size: 11px; margin-top:5px;"><strong><?php echo esc_html($photo['full_name']); ?></strong></div>
                    <div style="font-size: 10px; color:#666;"><?php echo esc_html($photo['caption']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
