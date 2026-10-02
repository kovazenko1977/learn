<?php
if (!defined('ABSPATH')) {
    exit;
}

class Memory_Pages_Memorial {

    /**
     * Fetch memorial by ID or Code
     */
    public static function get($id_or_code) {
        global $wpdb;
        $table = $wpdb->prefix . 'memorials';

        if (is_numeric($id_or_code) && strlen((string)$id_or_code) < 8) {
            $sql = $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id_or_code);
        } else {
            $sql = $wpdb->prepare("SELECT * FROM $table WHERE code = %s", $id_or_code);
        }

        return $wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Get photos for memorial
     */
    public static function get_photos($memorial_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'memorial_photos';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE memorial_id = %d ORDER BY is_main DESC, sort_order ASC, id ASC",
            $memorial_id
        ), ARRAY_A);
    }

    /**
     * Get relatives for memorial
     */
    public static function get_relatives($memorial_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'memorial_relatives';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE memorial_id = %d ORDER BY id ASC",
            $memorial_id
        ), ARRAY_A);
    }

    /**
     * Create or update memorial
     */
    public static function save($data, $user_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'memorials';

        $id = !empty($data['id']) ? intval($data['id']) : 0;
        $old_data = $id ? self::get($id) : null;

        $fields = array(
            'full_name'     => sanitize_text_field($data['full_name'] ?? ''),
            'birth_date'    => !empty($data['birth_date']) ? sanitize_text_field($data['birth_date']) : null,
            'death_date'    => !empty($data['death_date']) ? sanitize_text_field($data['death_date']) : null,
            'birth_place'   => sanitize_text_field($data['birth_place'] ?? ''),
            'cemetery'      => sanitize_text_field($data['cemetery'] ?? ''),
            'plot'          => sanitize_text_field($data['plot'] ?? ''),
            'row_number'    => sanitize_text_field($data['row_number'] ?? ''),
            'place_number'  => sanitize_text_field($data['place_number'] ?? ''),
            'biography'     => wp_kses_post($data['biography'] ?? ''),
            'status'        => sanitize_text_field($data['status'] ?? 'draft'),
            'main_photo_id' => intval($data['main_photo_id'] ?? 0),
            'main_photo_url'=> esc_url_raw($data['main_photo_url'] ?? ''),
            'updated_by'    => $user_id,
            'updated_at'    => current_time('mysql'),
        );

        if ($id > 0) {
            $wpdb->update($table, $fields, array('id' => $id));
            $code = $old_data['code'];

            // Log change in version history
            self::log_action($id, $user_id, 'updated_memorial', json_encode($old_data, JSON_UNESCAPED_UNICODE), json_encode($fields, JSON_UNESCAPED_UNICODE));
        } else {
            $fields['code'] = Memory_Pages_Rewrites::generate_unique_code();
            $fields['created_by'] = $user_id;
            $fields['created_at'] = current_time('mysql');

            $wpdb->insert($table, $fields);
            $id = $wpdb->insert_id;
            $code = $fields['code'];

            // Log change
            self::log_action($id, $user_id, 'created_memorial', '', json_encode($fields, JSON_UNESCAPED_UNICODE));
        }

        return array('id' => $id, 'code' => $code);
    }

    /**
     * Log version history
     */
    public static function log_action($memorial_id, $user_id, $action, $old_val = '', $new_val = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'memorial_logs';
        $wpdb->insert($table, array(
            'memorial_id' => $memorial_id,
            'user_id'     => $user_id,
            'action'      => $action,
            'old_value'   => $old_val,
            'new_value'   => $new_val,
            'created_at'  => current_time('mysql'),
        ));
    }
}
