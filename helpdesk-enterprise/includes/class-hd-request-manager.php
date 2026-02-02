<?php

if (!defined('ABSPATH')) {
    exit;
}

class HD_Request_Manager {
    public static function create_request($data) {
        global $wpdb;

        $category_id = intval($data['category_id']);
        $category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_categories WHERE id = %d", $category_id));
        if (!$category) return false;

        $dept_id = $category->department_id;
        $department = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_departments WHERE id = %d", $dept_id));
        $dept_settings = json_decode($department->settings, true);

        $deadline = HD_SLA::calculate_deadline($category->base_sla, $dept_settings, null, $category->priority);

        $request_data = array(
            'title' => sanitize_text_field($data['title']),
            'description' => wp_kses_post($data['description']),
            'category_id' => $category_id,
            'department_id' => $dept_id,
            'responsible_id' => get_current_user_id(),
            'executor_id' => $category->default_executor_id,
            'status' => 'new',
            'created_at' => current_time('mysql'),
            'deadline' => $deadline
        );

        $wpdb->insert("{$wpdb->prefix}hd_requests", $request_data);
        $request_id = $wpdb->insert_id;

        self::log_history($request_id, 'request_created', 0, null, json_encode($request_data));

        do_action('hd_request_created', $request_id, $request_data);

        return $request_id;
    }

    public static function update_status($request_id, $new_status) {
        global $wpdb;
        $old_request = $wpdb->get_row($wpdb->prepare("SELECT status FROM {$wpdb->prefix}hd_requests WHERE id = %d", $request_id));
        if (!$old_request) return false;

        $old_status = $old_request->status;

        $update_data = array('status' => $new_status);
        if ($new_status === 'completed') {
            $update_data['completed_at'] = current_time('mysql');
        }

        $wpdb->update("{$wpdb->prefix}hd_requests", $update_data, array('id' => $request_id));

        self::log_history($request_id, 'status_changed', get_current_user_id(), $old_status, $new_status);

        do_action('hd_status_changed', $request_id, $new_status, $old_status);

        return true;
    }

    public static function assign_executor($request_id, $executor_id) {
        global $wpdb;
        $old_request = $wpdb->get_row($wpdb->prepare("SELECT executor_id FROM {$wpdb->prefix}hd_requests WHERE id = %d", $request_id));
        if (!$old_request) return false;

        $old_executor_id = $old_request->executor_id;

        $wpdb->update("{$wpdb->prefix}hd_requests", array('executor_id' => $executor_id), array('id' => $request_id));

        self::log_history($request_id, 'executor_changed', get_current_user_id(), $old_executor_id, $executor_id);

        do_action('hd_executor_changed', $request_id, $executor_id, $old_executor_id);

        return true;
    }

    public static function update_deadline($request_id, $new_deadline) {
        global $wpdb;
        $old_request = $wpdb->get_row($wpdb->prepare("SELECT deadline FROM {$wpdb->prefix}hd_requests WHERE id = %d", $request_id));
        if (!$old_request) return false;

        $old_deadline = $old_request->deadline;

        $wpdb->update("{$wpdb->prefix}hd_requests", array('deadline' => $new_deadline), array('id' => $request_id));

        self::log_history($request_id, 'deadline_changed', get_current_user_id(), $old_deadline, $new_deadline);

        do_action('hd_deadline_changed', $request_id, $new_deadline, $old_deadline);

        return true;
    }

    public static function add_comment($request_id, $content) {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}hd_comments", array(
            'request_id' => $request_id,
            'user_id' => get_current_user_id(),
            'content' => wp_kses_post($content),
            'created_at' => current_time('mysql')
        ));
        $comment_id = $wpdb->insert_id;

        self::log_history($request_id, 'comment_added', get_current_user_id(), null, $content);

        do_action('hd_comment_added', $request_id, $comment_id);

        return $comment_id;
    }

    public static function add_photo($request_id, $file_url) {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}hd_photos", array(
            'request_id' => $request_id,
            'user_id' => get_current_user_id(),
            'file_url' => esc_url_raw($file_url),
            'created_at' => current_time('mysql')
        ));
        $photo_id = $wpdb->insert_id;

        self::log_history($request_id, 'photo_added', get_current_user_id(), null, $file_url);

        do_action('hd_photo_added', $request_id, $photo_id);

        return $photo_id;
    }

    public static function delete_comment($comment_id) {
        global $wpdb;
        $comment = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_comments WHERE id = %d", $comment_id));
        if (!$comment) return false;

        $wpdb->delete("{$wpdb->prefix}hd_comments", array('id' => $comment_id));
        self::log_history($comment->request_id, 'comment_deleted', get_current_user_id(), $comment->content, null);
        return true;
    }

    public static function delete_photo($photo_id) {
        global $wpdb;
        $photo = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_photos WHERE id = %d", $photo_id));
        if (!$photo) return false;

        $wpdb->delete("{$wpdb->prefix}hd_photos", array('id' => $photo_id));
        self::log_history($photo->request_id, 'photo_deleted', get_current_user_id(), $photo->file_url, null);
        return true;
    }

    public static function delete_request($request_id) {
        global $wpdb;
        $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_requests WHERE id = %d", $request_id));
        if (!$request) return false;

        // Log history before deleting the request itself or handle it differently?
        // Usually we log to a separate table so it stays.
        self::log_history($request_id, 'request_deleted', get_current_user_id(), json_encode($request), null);

        $wpdb->delete("{$wpdb->prefix}hd_requests", array('id' => $request_id));
        $wpdb->delete("{$wpdb->prefix}hd_comments", array('request_id' => $request_id));
        $wpdb->delete("{$wpdb->prefix}hd_photos", array('request_id' => $request_id));
        // We might want to keep history but it's linked to request_id.
        return true;
    }

    public static function log_history($request_id, $event_type, $user_id, $old_value, $new_value) {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}hd_history", array(
            'request_id' => $request_id,
            'event_type' => $event_type,
            'user_id' => $user_id,
            'old_value' => is_array($old_value) || is_object($old_value) ? json_encode($old_value) : $old_value,
            'new_value' => is_array($new_value) || is_object($new_value) ? json_encode($new_value) : $new_value,
            'created_at' => current_time('mysql')
        ));
    }
}
