<?php

if (!defined('ABSPATH')) {
    exit;
}

class HD_Dashboard {
    public function __construct() {
        add_shortcode('hd_dashboard', array($this, 'render_dashboard'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        add_action('wp_ajax_hd_create_request', array($this, 'ajax_create_request'));
        add_action('wp_ajax_hd_get_request_details', array($this, 'ajax_get_request_details'));
        add_action('wp_ajax_hd_add_comment', array($this, 'ajax_add_comment'));
        add_action('wp_ajax_hd_update_status', array($this, 'ajax_update_status'));
        add_action('wp_ajax_hd_assign_executor', array($this, 'ajax_assign_executor'));
        add_action('wp_ajax_hd_update_deadline', array($this, 'ajax_update_deadline'));
        add_action('wp_ajax_hd_delete_comment', array($this, 'ajax_delete_comment'));
        add_action('wp_ajax_hd_delete_photo', array($this, 'ajax_delete_photo'));
        add_action('wp_ajax_hd_delete_request', array($this, 'ajax_delete_request'));
        add_action('wp_ajax_hd_add_photo', array($this, 'ajax_add_photo'));
        add_action('wp_ajax_hd_save_user_settings', array($this, 'ajax_save_user_settings'));
    }

    public function ajax_save_user_settings() {
        check_ajax_referer('hd_nonce', 'nonce');
        $chat_id = sanitize_text_field($_POST['telegram_chat_id']);
        update_user_meta(get_current_user_id(), 'hd_telegram_chat_id', $chat_id);
        wp_send_json_success();
    }

    public function ajax_delete_comment() {
        check_ajax_referer('hd_nonce', 'nonce');
        if (!current_user_can('hd_delete_data')) wp_send_json_error('Forbidden');

        $id = intval($_POST['id']);
        if (HD_Request_Manager::delete_comment($id)) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    public function ajax_add_photo() {
        check_ajax_referer('hd_nonce', 'nonce');
        $id = intval($_POST['request_id']);

        if (!$this->can_interact_with_request($id)) {
            wp_send_json_error('Forbidden');
        }

        if (!empty($_FILES['photo']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            $upload = wp_handle_upload($_FILES['photo'], array('test_form' => false));
            if (isset($upload['url'])) {
                HD_Request_Manager::add_photo($id, $upload['url']);
                wp_send_json_success();
            }
        }
        wp_send_json_error();
    }

    public function ajax_delete_request() {
        check_ajax_referer('hd_nonce', 'nonce');
        if (!current_user_can('hd_delete_data')) wp_send_json_error('Forbidden');

        $id = intval($_POST['id']);
        if (HD_Request_Manager::delete_request($id)) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    public function ajax_delete_photo() {
        check_ajax_referer('hd_nonce', 'nonce');
        if (!current_user_can('hd_delete_data')) wp_send_json_error('Forbidden');

        $id = intval($_POST['id']);
        if (HD_Request_Manager::delete_photo($id)) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    public function ajax_create_request() {
        check_ajax_referer('hd_nonce', 'nonce');
        if (!current_user_can('hd_create_requests')) wp_send_json_error('Forbidden');

        $id = HD_Request_Manager::create_request($_POST);
        if ($id) {
            if (!empty($_FILES['photo']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                $upload = wp_handle_upload($_FILES['photo'], array('test_form' => false));
                if (isset($upload['url'])) {
                    HD_Request_Manager::add_photo($id, $upload['url']);
                }
            }
            wp_send_json_success(array('id' => $id, 'message' => __('Заявка создана!', 'helpdesk-enterprise')));
        } else {
            wp_send_json_error(__('Не удалось создать заявку.', 'helpdesk-enterprise'));
        }
    }

    public function ajax_get_request_details() {
        check_ajax_referer('hd_nonce', 'nonce');
        global $wpdb;
        $id = intval($_POST['id']);

        $request = $wpdb->get_row($wpdb->prepare("SELECT r.*, c.name as cat_name, d.name as dept_name FROM {$wpdb->prefix}hd_requests r
                  LEFT JOIN {$wpdb->prefix}hd_categories c ON r.category_id = c.id
                  LEFT JOIN {$wpdb->prefix}hd_departments d ON r.department_id = d.id WHERE r.id = %d", $id));

        if (!$request) wp_send_json_error('Not found');

        // Permission check
        if (!current_user_can('hd_view_all_requests')) {
            if (current_user_can('hd_view_dept_requests')) {
                $is_manager = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}hd_departments WHERE id = %d AND manager_id = %d", $request->department_id, get_current_user_id()));
                if (!$is_manager) wp_send_json_error('Forbidden');
            } else if (get_current_user_id() != $request->responsible_id && get_current_user_id() != $request->executor_id) {
                wp_send_json_error('Forbidden');
            }
        }

        $comments = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_comments WHERE request_id = %d ORDER BY created_at DESC", $id));
        $history = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_history WHERE request_id = %d ORDER BY created_at DESC", $id));
        $photos = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_photos WHERE request_id = %d ORDER BY created_at DESC", $id));

        ob_start();
        include HD_PATH . 'templates/request-details.php';
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    public function ajax_add_comment() {
        check_ajax_referer('hd_nonce', 'nonce');
        $id = intval($_POST['request_id']);
        $content = sanitize_textarea_field($_POST['content']);

        if (!$this->can_interact_with_request($id)) {
            wp_send_json_error('Forbidden');
        }

        if (HD_Request_Manager::add_comment($id, $content)) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    public function ajax_assign_executor() {
        check_ajax_referer('hd_nonce', 'nonce');
        $id = intval($_POST['request_id']);
        $executor_id = intval($_POST['executor_id']);

        if (!current_user_can('hd_manage_dept_requests') && !current_user_can('hd_manage_all')) {
            wp_send_json_error('Forbidden');
        }

        if (HD_Request_Manager::assign_executor($id, $executor_id)) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    public function ajax_update_deadline() {
        check_ajax_referer('hd_nonce', 'nonce');
        $id = intval($_POST['request_id']);
        $deadline = sanitize_text_field($_POST['deadline']);

        if (!current_user_can('hd_manage_dept_requests') && !current_user_can('hd_manage_all')) {
            wp_send_json_error('Forbidden');
        }

        if (HD_Request_Manager::update_deadline($id, $deadline)) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    public function ajax_update_status() {
        check_ajax_referer('hd_nonce', 'nonce');
        $id = intval($_POST['request_id']);
        $status = sanitize_text_field($_POST['status']);

        if (!$this->can_interact_with_request($id) || (!current_user_can('hd_update_status') && !current_user_can('hd_manage_dept_requests') && !current_user_can('hd_manage_all'))) {
            wp_send_json_error('Forbidden');
        }

        if (HD_Request_Manager::update_status($id, $status)) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    private function can_interact_with_request($id) {
        global $wpdb;
        $request = $wpdb->get_row($wpdb->prepare("SELECT department_id, responsible_id, executor_id FROM {$wpdb->prefix}hd_requests WHERE id = %d", $id));
        if (!$request) return false;

        $user_id = get_current_user_id();
        if (current_user_can('hd_manage_all')) return true;
        if (current_user_can('hd_manage_dept_requests')) {
            $is_manager = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}hd_departments WHERE id = %d AND manager_id = %d", $request->department_id, $user_id));
            if ($is_manager) return true;
        }
        if ($user_id == $request->responsible_id || $user_id == $request->executor_id) return true;

        return false;
    }

    public function enqueue_assets() {
        wp_enqueue_style('hd-style', HD_URL . 'assets/css/style.css');
        wp_enqueue_script('hd-script', HD_URL . 'assets/js/script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('hd-script', 'hd_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hd_nonce')
        ));
    }

    public function render_dashboard() {
        if (!is_user_logged_in()) {
            return __('Пожалуйста, войдите в систему для доступа к Helpdesk.', 'helpdesk-enterprise');
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $query = "SELECT r.*, c.name as cat_name, d.name as dept_name FROM {$wpdb->prefix}hd_requests r
                  LEFT JOIN {$wpdb->prefix}hd_categories c ON r.category_id = c.id
                  LEFT JOIN {$wpdb->prefix}hd_departments d ON r.department_id = d.id WHERE 1=1";

        if (current_user_can('hd_view_all_requests')) {
            // No filter
        } else if (current_user_can('hd_view_dept_requests')) {
            $managed_depts = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}hd_departments WHERE manager_id = %d", $user_id));
            if ($managed_depts) {
                $query .= " AND r.department_id IN (" . implode(',', array_map('intval', $managed_depts)) . ")";
            } else {
                $query .= " AND 1=0";
            }
        } else if (current_user_can('hd_view_own_assigned_requests')) {
            $query .= $wpdb->prepare(" AND r.executor_id = %d", $user_id);
        } else if (current_user_can('hd_view_own_requests')) {
            $query .= $wpdb->prepare(" AND r.responsible_id = %d", $user_id);
        } else {
            return __('У вас нет прав для просмотра этой страницы.', 'helpdesk-enterprise');
        }

        if (!empty($_GET['status_filter'])) {
            $query .= $wpdb->prepare(" AND r.status = %s", sanitize_text_field($_GET['status_filter']));
        }
        if (!empty($_GET['cat_filter'])) {
            $query .= $wpdb->prepare(" AND r.category_id = %d", intval($_GET['cat_filter']));
        }

        $requests = $wpdb->get_results($query);
        $categories = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}hd_categories");

        // Calculate Stats
        $stats = array(
            'total' => count($requests),
            'new' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'overdue' => 0
        );

        $now = current_time('timestamp');
        foreach ($requests as $r) {
            if (isset($stats[$r->status])) {
                $stats[$r->status]++;
            } else if ($r->status === 'new') {
                $stats['new']++;
            }

            if ($r->status !== 'completed' && strtotime($r->deadline) < $now) {
                $stats['overdue']++;
            }
        }

        ob_start();
        include HD_PATH . 'templates/dashboard.php';
        return ob_get_clean();
    }
}

new HD_Dashboard();
