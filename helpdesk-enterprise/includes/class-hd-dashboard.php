<?php

if (!defined('ABSPATH')) {
    exit;
}

class HD_Dashboard {
    public function __construct() {
        add_shortcode('hd_dashboard', array($this, 'render_dashboard'));
        add_shortcode('hd_request_form', array($this, 'render_request_form'));
        add_shortcode('hd_request_list', array($this, 'render_request_list'));
        add_shortcode('hd_admin_settings', array($this, 'render_admin_settings'));
        add_shortcode('hd_admin_departments', array($this, 'render_admin_departments'));
        add_shortcode('hd_admin_categories', array($this, 'render_admin_categories'));
        add_shortcode('hd_admin_users', array($this, 'render_admin_users'));
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
        add_action('wp_ajax_hd_login', array($this, 'ajax_login'));
        add_action('wp_ajax_hd_logout', array($this, 'ajax_logout'));
        add_action('wp_ajax_nopriv_hd_login', array($this, 'ajax_login'));
        add_action('wp_ajax_hd_save_user_settings', array($this, 'ajax_save_user_settings'));
    }

    public function ajax_login() {
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];

        if (HD_Auth::login($username, $password)) {
            wp_send_json_success();
        } else {
            wp_send_json_error(__('Неверное имя пользователя или пароль', 'helpdesk-enterprise'));
        }
    }

    public function ajax_logout() {
        HD_Auth::logout();
        wp_send_json_success();
    }

    public function ajax_save_user_settings() {
        check_ajax_referer('hd_nonce', 'nonce');
        $chat_id = sanitize_text_field($_POST['telegram_chat_id']);
        global $wpdb;
        $wpdb->update("{$wpdb->prefix}hd_users", array('telegram_chat_id' => $chat_id), array('id' => HD_Auth::get_user_id()));
        wp_send_json_success();
    }

    public function ajax_delete_comment() {
        check_ajax_referer('hd_nonce', 'nonce');
        if (!HD_Auth::current_user_can('hd_delete_data')) wp_send_json_error('Forbidden');

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
        if (!HD_Auth::current_user_can('hd_delete_data')) wp_send_json_error('Forbidden');

        $id = intval($_POST['id']);
        if (HD_Request_Manager::delete_request($id)) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    public function ajax_delete_photo() {
        check_ajax_referer('hd_nonce', 'nonce');
        if (!HD_Auth::current_user_can('hd_delete_data')) wp_send_json_error('Forbidden');

        $id = intval($_POST['id']);
        if (HD_Request_Manager::delete_photo($id)) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    public function ajax_create_request() {
        check_ajax_referer('hd_nonce', 'nonce');
        if (!HD_Auth::current_user_can('hd_create_requests')) wp_send_json_error('Forbidden');

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

        $user_id = HD_Auth::get_user_id();

        // Permission check
        if (!HD_Auth::current_user_can('hd_view_all')) {
            if (HD_Auth::current_user_can('hd_view_dept')) {
                $is_manager = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}hd_departments WHERE id = %d AND manager_id = %d", $request->department_id, $user_id));
                if (!$is_manager) wp_send_json_error('Forbidden');
            } else if ($user_id != $request->responsible_id && $user_id != $request->executor_id) {
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

        if (!HD_Auth::current_user_can('hd_manage_dept') && !HD_Auth::current_user_can('hd_manage_all')) {
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

        if (!HD_Auth::current_user_can('hd_manage_dept') && !HD_Auth::current_user_can('hd_manage_all')) {
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

        if (!$this->can_interact_with_request($id) || (!HD_Auth::current_user_can('hd_update_status') && !HD_Auth::current_user_can('hd_manage_dept') && !HD_Auth::current_user_can('hd_manage_all'))) {
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

        $user_id = HD_Auth::get_user_id();
        if (HD_Auth::current_user_can('hd_manage_all')) return true;
        if (HD_Auth::current_user_can('hd_manage_dept')) {
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
        if (!HD_Auth::is_logged_in()) {
            ob_start();
            include HD_PATH . 'templates/login.php';
            return ob_get_clean();
        }

        global $wpdb;
        $user_id = HD_Auth::get_user_id();

        $requests_data = $this->get_filtered_requests();
        $requests = $requests_data['requests'];
        $categories = $requests_data['categories'];
        $stats = $requests_data['stats'];

        ob_start();
        include HD_PATH . 'templates/dashboard.php';
        return ob_get_clean();
    }

    public function render_request_form() {
        if (!HD_Auth::is_logged_in()) {
            ob_start();
            include HD_PATH . 'templates/login.php';
            return ob_get_clean();
        }

        if (!HD_Auth::current_user_can('hd_create_requests')) {
            return __('У вас нет прав для создания заявок.', 'helpdesk-enterprise');
        }

        global $wpdb;
        $categories = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}hd_categories");

        ob_start();
        ?>
        <div class="hd-dashboard-wrapper standalone-form">
            <section class="hd-create-form-container">
                <h3 style="margin-top: 0;"><?php _e('Создать новую заявку', 'helpdesk-enterprise'); ?></h3>
                <form id="hd-create-form">
                    <div class="hd-form-group">
                        <label><?php _e('Заголовок', 'helpdesk-enterprise'); ?></label>
                        <input type="text" name="title" class="hd-input" required placeholder="<?php _e('Краткая суть проблемы...', 'helpdesk-enterprise'); ?>">
                    </div>
                    <div class="hd-form-group">
                        <label><?php _e('Описание', 'helpdesk-enterprise'); ?></label>
                        <textarea name="description" class="hd-textarea" rows="6" required placeholder="<?php _e('Подробное описание...', 'helpdesk-enterprise'); ?>"></textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="hd-form-group">
                            <label><?php _e('Категория', 'helpdesk-enterprise'); ?></label>
                            <select name="category_id" class="hd-input" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat->id; ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="hd-form-group">
                            <label><?php _e('Прикрепить фото', 'helpdesk-enterprise'); ?></label>
                            <input type="file" name="photo" class="hd-input" accept="image/*">
                        </div>
                    </div>
                    <button type="submit" class="hd-btn hd-btn-primary" style="width: 100%; padding: 12px; font-size: 16px;"><?php _e('Отправить заявку', 'helpdesk-enterprise'); ?></button>
                </form>
            </section>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_request_list() {
        if (!HD_Auth::is_logged_in()) {
            ob_start();
            include HD_PATH . 'templates/login.php';
            return ob_get_clean();
        }

        $requests_data = $this->get_filtered_requests();
        $requests = $requests_data['requests'];
        $categories = $requests_data['categories'];

        ob_start();
        ?>
        <div class="hd-dashboard-wrapper standalone-list">
            <section class="hd-controls">
                <form method="get" style="display: flex; gap: 10px; align-items: center;">
                    <select name="status_filter" class="hd-filter-select">
                        <option value=""><?php _e('Все статусы', 'helpdesk-enterprise'); ?></option>
                        <option value="new" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'new'); ?>><?php _e('Новые', 'helpdesk-enterprise'); ?></option>
                        <option value="in_progress" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'in_progress'); ?>><?php _e('В работе', 'helpdesk-enterprise'); ?></option>
                        <option value="completed" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'completed'); ?>><?php _e('Выполнены', 'helpdesk-enterprise'); ?></option>
                    </select>
                    <button type="submit" class="hd-btn hd-btn-primary"><?php _e('Фильтр', 'helpdesk-enterprise'); ?></button>
                </form>
            </section>

            <div class="hd-table-container">
                <table class="hd-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th><?php _e('Заявка', 'helpdesk-enterprise'); ?></th>
                            <th><?php _e('Статус', 'helpdesk-enterprise'); ?></th>
                            <th><?php _e('Дедлайн', 'helpdesk-enterprise'); ?></th>
                            <th style="text-align: right;"><?php _e('Действия', 'helpdesk-enterprise'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>#<?php echo $request->id; ?></td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo esc_html($request->title); ?></div>
                                </td>
                                <td><span class="hd-badge badge-<?php echo $request->status; ?>">
                                    <?php
                                        $status_labels = array('new' => 'Новая', 'in_progress' => 'В работе', 'pending' => 'Ожидание', 'completed' => 'Выполнена', 'rejected' => 'Отклонена');
                                        echo isset($status_labels[$request->status]) ? $status_labels[$request->status] : $request->status;
                                    ?>
                                </span></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($request->deadline)); ?></td>
                                <td style="text-align: right;">
                                    <button class="hd-btn hd-view-request" data-id="<?php echo $request->id; ?>" style="background: #f1f5f9; color: var(--hd-primary);"><?php _e('Открыть', 'helpdesk-enterprise'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="hd-modal">
                <div class="hd-modal-content">
                    <div class="hd-modal-header">
                        <h3 style="margin: 0;"><?php _e('Детали заявки', 'helpdesk-enterprise'); ?></h3>
                        <span class="hd-close">&times;</span>
                    </div>
                    <div class="hd-modal-body" id="hd-request-details"></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_filtered_requests() {
        global $wpdb;
        $user_id = HD_Auth::get_user_id();
        $query = "SELECT r.*, c.name as cat_name, d.name as dept_name FROM {$wpdb->prefix}hd_requests r
                  LEFT JOIN {$wpdb->prefix}hd_categories c ON r.category_id = c.id
                  LEFT JOIN {$wpdb->prefix}hd_departments d ON r.department_id = d.id WHERE 1=1";

        if (HD_Auth::current_user_can('hd_view_all')) {
        } else if (HD_Auth::current_user_can('hd_view_dept')) {
            $managed_depts = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}hd_departments WHERE manager_id = %d", $user_id));
            if ($managed_depts) {
                $query .= " AND r.department_id IN (" . implode(',', array_map('intval', $managed_depts)) . ")";
            } else {
                $query .= " AND 1=0";
            }
        } else if (HD_Auth::current_user_can('hd_view_assigned')) {
            $query .= $wpdb->prepare(" AND r.executor_id = %d", $user_id);
        } else if (HD_Auth::current_user_can('hd_view_own')) {
            $query .= $wpdb->prepare(" AND r.responsible_id = %d", $user_id);
        }

        if (!empty($_GET['status_filter'])) {
            $query .= $wpdb->prepare(" AND r.status = %s", sanitize_text_field($_GET['status_filter']));
        }
        if (!empty($_GET['cat_filter'])) {
            $query .= $wpdb->prepare(" AND r.category_id = %d", intval($_GET['cat_filter']));
        }

        $requests = $wpdb->get_results($query);
        $categories = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}hd_categories");

        $stats = array('total' => count($requests), 'new' => 0, 'in_progress' => 0, 'completed' => 0, 'overdue' => 0);
        $now = current_time('timestamp');
        foreach ($requests as $r) {
            if (isset($stats[$r->status])) $stats[$r->status]++;
            else if ($r->status === 'new') $stats['new']++;
            if ($r->status !== 'completed' && strtotime($r->deadline) < $now) $stats['overdue']++;
        }

        return compact('requests', 'categories', 'stats');
    }

    public function render_admin_settings() {
        if (!HD_Auth::is_logged_in() || !HD_Auth::current_user_can('hd_admin_shortcodes')) return $this->admin_forbidden();
        ob_start();
        include HD_PATH . 'templates/admin-settings.php';
        return '<div class="hd-dashboard-wrapper">' . ob_get_clean() . '</div>';
    }

    public function render_admin_departments() {
        if (!HD_Auth::is_logged_in() || !HD_Auth::current_user_can('hd_admin_shortcodes')) return $this->admin_forbidden();
        global $wpdb;
        $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hd_departments");
        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $edit_item = $edit_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_departments WHERE id = %d", $edit_id)) : null;
        $settings = $edit_item ? json_decode($edit_item->settings, true) : array();
        ob_start();
        include HD_PATH . 'templates/admin-departments.php';
        return '<div class="hd-dashboard-wrapper">' . ob_get_clean() . '</div>';
    }

    public function render_admin_categories() {
        if (!HD_Auth::is_logged_in() || !HD_Auth::current_user_can('hd_admin_shortcodes')) return $this->admin_forbidden();
        global $wpdb;
        $items = $wpdb->get_results("SELECT c.*, d.name as dept_name FROM {$wpdb->prefix}hd_categories c LEFT JOIN {$wpdb->prefix}hd_departments d ON c.department_id = d.id");
        $depts = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}hd_departments");
        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $edit_item = $edit_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_categories WHERE id = %d", $edit_id)) : null;
        ob_start();
        include HD_PATH . 'templates/admin-categories.php';
        return '<div class="hd-dashboard-wrapper">' . ob_get_clean() . '</div>';
    }

    public function render_admin_users() {
        if (!HD_Auth::is_logged_in() || !HD_Auth::current_user_can('hd_admin_shortcodes')) return $this->admin_forbidden();
        global $wpdb;
        $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hd_users");
        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $edit_item = $edit_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_users WHERE id = %d", $edit_id)) : null;
        ob_start();
        include HD_PATH . 'templates/admin-users.php';
        return '<div class="hd-dashboard-wrapper">' . ob_get_clean() . '</div>';
    }

    private function admin_forbidden() {
        ob_start();
        if (HD_Auth::is_logged_in()) {
            echo '<div class="hd-dashboard-wrapper" style="text-align:center; padding: 20px;">';
            echo '<p style="color:var(--hd-danger); font-weight:bold; margin-bottom:20px;">' . __('Доступ разрешен только администраторам.', 'helpdesk-enterprise') . '</p>';
            echo '<p style="font-size:14px; color:var(--hd-secondary);">' . sprintf(__('Вы вошли как %s (%s). Для доступа к настройкам необходимо войти под учетной записью администратора.', 'helpdesk-enterprise'), '<b>'.esc_html(HD_Auth::get_user()->display_name).'</b>', '<i>'.esc_html(HD_Auth::get_user()->role).'</i>') . '</p>';
            echo '</div>';
        }
        include HD_PATH . 'templates/login.php';
        return ob_get_clean();
    }
}

new HD_Dashboard();
