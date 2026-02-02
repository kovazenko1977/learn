<?php

if (!defined('ABSPATH')) {
    exit;
}

class HD_Telegram {
    public function __construct() {
        add_action('hd_request_created', array($this, 'notify_request_created'), 10, 2);
        add_action('hd_status_changed', array($this, 'notify_status_changed'), 10, 3);
        add_action('hd_executor_changed', array($this, 'notify_executor_changed'), 10, 3);
        add_action('hd_deadline_changed', array($this, 'notify_deadline_changed'), 10, 3);
    }

    public static function send_message($chat_id, $message) {
        $token = get_option('hd_telegram_token');
        if (!$token || !$chat_id) return;

        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        wp_remote_post($url, array(
            'body' => array(
                'chat_id' => $chat_id,
                'text' => $message,
                'parse_mode' => 'HTML'
            )
        ));
    }

    private function get_admin_chat_id() {
        return get_option('hd_telegram_admin_chat_id'); // Assuming this exists in settings
    }

    public function notify_request_created($request_id, $data) {
        $msg = "🆕 <b>New Request #{$request_id}</b>\n";
        $msg .= "Title: {$data['title']}\n";
        $msg .= "Deadline: {$data['deadline']}";

        $this->notify_all_relevant($request_id, $msg);
    }

    public function notify_status_changed($request_id, $new_status, $old_status) {
        $msg = "🔄 <b>Request #{$request_id} status changed</b>\n";
        $msg .= "Old: {$old_status}\n";
        $msg .= "New: <b>{$new_status}</b>";

        $this->notify_all_relevant($request_id, $msg);
    }

    public function notify_executor_changed($request_id, $new_executor_id, $old_executor_id) {
        $new_exec = get_userdata($new_executor_id);
        $msg = "👤 <b>Executor changed for Request #{$request_id}</b>\n";
        $msg .= "New Executor: " . ($new_exec ? $new_exec->display_name : 'None');

        $this->notify_all_relevant($request_id, $msg);
    }

    public function notify_deadline_changed($request_id, $new_deadline, $old_deadline) {
        $msg = "📅 <b>Deadline updated for Request #{$request_id}</b>\n";
        $msg .= "New Deadline: {$new_deadline}";

        $this->notify_all_relevant($request_id, $msg);
    }

    private function notify_all_relevant($request_id, $message) {
        global $wpdb;
        $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_requests WHERE id = %d", $request_id));
        if (!$request) return;

        // Notify responsible
        $resp_chat = get_user_meta($request->responsible_id, 'hd_telegram_chat_id', true);
        if ($resp_chat) self::send_message($resp_chat, $message);

        // Notify executor
        if ($request->executor_id) {
            $exec_chat = get_user_meta($request->executor_id, 'hd_telegram_chat_id', true);
            if ($exec_chat) self::send_message($exec_chat, $message);
        }

        // Notify Dept Head
        $dept = $wpdb->get_row($wpdb->prepare("SELECT manager_id FROM {$wpdb->prefix}hd_departments WHERE id = %d", $request->department_id));
        if ($dept && $dept->manager_id) {
            $mgr_chat = get_user_meta($dept->manager_id, 'hd_telegram_chat_id', true);
            if ($mgr_chat) self::send_message($mgr_chat, $message);
        }

        // Notify Admin (General chat)
        $admin_chat = $this->get_admin_chat_id();
        if ($admin_chat) self::send_message($admin_chat, $message);
    }
}

new HD_Telegram();
