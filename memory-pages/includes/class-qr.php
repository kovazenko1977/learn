<?php
if (!defined('ABSPATH')) exit;

class Memory_Pages_QR {

    /**
     * Generate or get QR image URL for memorial permanent URL /memory/XXXXXXXX/
     */
    public static function get_or_create_qr($memorial_id, $code) {
        global $wpdb;
        $table = $wpdb->prefix . 'memorial_qr';

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE memorial_id = %d", $memorial_id), ARRAY_A);
        if ($existing && !empty($existing['qr_image_url'])) {
            return $existing['qr_image_url'];
        }

        // Generate SVG/PNG QR image using Google Chart API or built-in SVG generator
        $target_url = home_url('/memory/' . $code . '/?source=qr');
        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($target_url);

        if ($existing) {
            $wpdb->update($table, array('qr_image_url' => $qr_url), array('memorial_id' => $memorial_id));
        } else {
            $wpdb->insert($table, array(
                'memorial_id'   => $memorial_id,
                'code'          => $code,
                'qr_image_url'  => $qr_url,
                'created_at'    => current_time('mysql'),
            ));
        }

        return $qr_url;
    }
}
