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
        if (!get_option('hd_notify_on_create', 1)) return;
        $msg = "🆕 <b>Новая заявка #{$request_id}</b>\n";
        $msg .= "Заголовок: {$data['title']}\n";
        $msg .= "Срок (SLA): {$data['deadline']}";

        $this->notify_all_relevant($request_id, $msg);
    }

    public function notify_status_changed($request_id, $new_status, $old_status) {
        if (!get_option('hd_notify_on_status', 1)) return;
        $msg = "🔄 <b>Статус заявки #{$request_id} изменен</b>\n";
        $msg .= "Старый: {$old_status}\n";
        $msg .= "Новый: <b>{$new_status}</b>";

        $this->notify_all_relevant($request_id, $msg);
    }

    public function notify_executor_changed($request_id, $new_executor_id, $old_executor_id) {
        $new_exec = HD_Auth::get_user_by_id($new_executor_id);
        $msg = "👤 <b>Исполнитель заявки #{$request_id} изменен</b>\n";
        $msg .= "Новый исполнитель: " . ($new_exec ? $new_exec->display_name : 'Не назначен');

        $this->notify_all_relevant($request_id, $msg);
    }

    public function notify_deadline_changed($request_id, $new_deadline, $old_deadline) {
        $msg = "📅 <b>Срок выполнения заявки #{$request_id} обновлен</b>\n";
        $msg .= "Новый срок: {$new_deadline}";

        $this->notify_all_relevant($request_id, $msg);
    }

    private function notify_all_relevant($request_id, $message) {
        global $wpdb;
        $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_requests WHERE id = %d", $request_id));
        if (!$request) return;

        // Notify responsible
        $resp = HD_Auth::get_user_by_id($request->responsible_id);
        if ($resp && $resp->telegram_chat_id) self::send_message($resp->telegram_chat_id, $message);

        // Notify executor
        if ($request->executor_id) {
            $exec = HD_Auth::get_user_by_id($request->executor_id);
            if ($exec && $exec->telegram_chat_id) self::send_message($exec->telegram_chat_id, $message);
        }

        // Notify Dept Head
        $dept = $wpdb->get_row($wpdb->prepare("SELECT manager_id FROM {$wpdb->prefix}hd_departments WHERE id = %d", $request->department_id));
        if ($dept && $dept->manager_id) {
            $mgr = HD_Auth::get_user_by_id($dept->manager_id);
            if ($mgr && $mgr->telegram_chat_id) self::send_message($mgr->telegram_chat_id, $message);
        }

        // Notify Admin (General chat)
        $admin_chat = $this->get_admin_chat_id();
        if ($admin_chat) self::send_message($admin_chat, $message);
    }
}

new HD_Telegram();
