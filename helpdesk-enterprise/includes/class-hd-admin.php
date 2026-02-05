<?php

if (!defined('ABSPATH')) {
    exit;
}

class HD_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menus'));
        add_action('init', array($this, 'handle_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function enqueue_admin_assets() {
        wp_enqueue_style('hd-admin-style', HD_URL . 'assets/css/style.css');
        // Add some admin-specific overrides for beautiful forms in console
        wp_add_inline_style('hd-admin-style', "
            .wrap h1 { color: #1e293b; font-weight: 800; margin-bottom: 24px; }
            .hd-admin-standalone { padding: 0; }
            .form-table th { font-weight: 600; color: #64748b; width: 200px; }
            .wp-list-table { border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; }
            .hd-create-form-container { border: 1px solid #e2e8f0; background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 30px; }
            .hd-input, .hd-textarea { background: #fff; border-color: #cbd5e1; font-size: 14px; padding: 10px 14px; }
            .hd-btn-primary { background: #2563eb !important; border: none !important; box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important; }
            .hd-admin-stats-grid .hd-admin-stat-card { border-top-width: 6px !important; }
            .hd-title { color: #1e293b; font-size: 24px; font-weight: 800; }
            #adminmenu .wp-has-current-submenu.wp-menu-open { background-color: #2563eb; }
        ");
    }

    public function add_menus() {
        add_menu_page(
            __('Поддержка', 'helpdesk-enterprise'),
            __('Поддержка', 'helpdesk-enterprise'),
            'hd_manage_settings',
            'hd-main',
            array($this, 'render_main_page'),
            'dashicons-sos',
            30
        );

        add_submenu_page(
            'hd-main',
            __('Отделы', 'helpdesk-enterprise'),
            __('Отделы', 'helpdesk-enterprise'),
            'hd_manage_settings',
            'hd-departments',
            array($this, 'render_departments_page')
        );

        add_submenu_page(
            'hd-main',
            __('Категории', 'helpdesk-enterprise'),
            __('Категории', 'helpdesk-enterprise'),
            'hd_manage_settings',
            'hd-categories',
            array($this, 'render_categories_page')
        );

        add_submenu_page(
            'hd-main',
            __('Настройки', 'helpdesk-enterprise'),
            __('Настройки', 'helpdesk-enterprise'),
            'hd_manage_settings',
            'hd-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'hd-main',
            __('Пользователи', 'helpdesk-enterprise'),
            __('Пользователи', 'helpdesk-enterprise'),
            'hd_manage_settings',
            'hd-users',
            array($this, 'render_users_page')
        );
    }

    public function handle_actions() {
        if (!isset($_POST['hd_action']) || !check_admin_referer('hd_admin_action')) {
            return;
        }

        global $wpdb;

        switch ($_POST['hd_action']) {
            case 'save_department':
                $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                $name = sanitize_text_field($_POST['name']);
                $manager_id = intval($_POST['manager_id']);
                $settings = json_encode(array(
                    'working_hours' => array(
                        'start' => sanitize_text_field($_POST['working_start']),
                        'end' => sanitize_text_field($_POST['working_end']),
                    ),
                    'lunch_break' => array(
                        'start' => sanitize_text_field($_POST['lunch_start']),
                        'end' => sanitize_text_field($_POST['lunch_end']),
                    ),
                    'weekends' => isset($_POST['weekends']) ? array_map('intval', $_POST['weekends']) : array(),
                    'holidays' => array_filter(array_map('trim', explode("\n", $_POST['holidays']))),
                ));

                if ($id) {
                    $wpdb->update("{$wpdb->prefix}hd_departments", array('name' => $name, 'manager_id' => $manager_id, 'settings' => $settings), array('id' => $id));
                } else {
                    $wpdb->insert("{$wpdb->prefix}hd_departments", array('name' => $name, 'manager_id' => $manager_id, 'settings' => $settings));
                }
                $this->smart_redirect('hd-departments');
                exit;

            case 'delete_department':
                $id = intval($_POST['id']);
                $wpdb->delete("{$wpdb->prefix}hd_departments", array('id' => $id));
                $this->smart_redirect('hd-departments');
                exit;

            case 'save_category':
                $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                $name = sanitize_text_field($_POST['name']);
                $department_id = intval($_POST['department_id']);
                $base_sla = intval($_POST['base_sla']);
                $priority = sanitize_text_field($_POST['priority']);
                $default_executor_id = intval($_POST['default_executor_id']);

                if ($id) {
                    $wpdb->update("{$wpdb->prefix}hd_categories", array('name' => $name, 'department_id' => $department_id, 'base_sla' => $base_sla, 'priority' => $priority, 'default_executor_id' => $default_executor_id), array('id' => $id));
                } else {
                    $wpdb->insert("{$wpdb->prefix}hd_categories", array('name' => $name, 'department_id' => $department_id, 'base_sla' => $base_sla, 'priority' => $priority, 'default_executor_id' => $default_executor_id));
                }
                $this->smart_redirect('hd-categories');
                exit;

            case 'save_settings':
                update_option('hd_company_name', sanitize_text_field($_POST['company_name']));
                update_option('hd_telegram_token', sanitize_text_field($_POST['telegram_token']));
                update_option('hd_telegram_bot_name', sanitize_text_field($_POST['telegram_bot_name']));
                update_option('hd_telegram_admin_chat_id', sanitize_text_field($_POST['telegram_admin_chat_id']));
                update_option('hd_default_sla', intval($_POST['default_sla']));
                update_option('hd_notify_on_create', isset($_POST['notify_on_create']) ? 1 : 0);
                update_option('hd_notify_on_status', isset($_POST['notify_on_status']) ? 1 : 0);
                $this->smart_redirect('hd-settings');
                exit;

            case 'save_user':
                $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                $username = sanitize_text_field($_POST['username']);
                $phone = sanitize_text_field($_POST['phone']);
                $display_name = sanitize_text_field($_POST['display_name']);
                $role = sanitize_text_field($_POST['role']);
                $password = $_POST['password'];

                $data = array(
                    'username' => $username,
                    'phone' => $phone,
                    'display_name' => $display_name,
                    'role' => $role
                );

                if (!$id) {
                    $data['api_token'] = wp_generate_password(32, false);
                }

                if (!empty($password)) {
                    $data['password'] = password_hash($password, PASSWORD_DEFAULT);
                }

                if ($id) {
                    $wpdb->update("{$wpdb->prefix}hd_users", $data, array('id' => $id));
                } else {
                    $wpdb->insert("{$wpdb->prefix}hd_users", $data);
                }
                wp_redirect(admin_url('admin.php?page=hd-users&message=saved'));
                exit;

            case 'delete_user':
                $id = intval($_POST['id']);
                $wpdb->delete("{$wpdb->prefix}hd_users", array('id' => $id));
                $this->smart_redirect('hd-users');
                exit;

            case 'reset_all_data':
                if (HD_Auth::current_user_can('hd_manage_settings')) {
                    HD_DB::reset_all_data();
                    $this->smart_redirect('hd-settings');
                }
                exit;
        }
    }

    private function smart_redirect($page) {
        if (is_admin()) {
            wp_redirect(admin_url('admin.php?page=' . $page . '&message=success'));
        } else {
            // Frontend redirect - back to the same page
            wp_redirect(remove_query_arg('edit', wp_get_referer()));
        }
    }

    public function render_main_page() {
        global $wpdb;
        $stats = array(
            'requests'    => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hd_requests"),
            'users'       => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hd_users"),
            'departments' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hd_departments"),
            'categories'  => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hd_categories"),
            'new'         => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hd_requests WHERE status = 'new'"),
            'in_progress' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hd_requests WHERE status = 'in_progress'"),
            'overdue'     => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}hd_requests WHERE status != 'completed' AND deadline < %s", current_time('mysql'))),
        );

        include HD_PATH . 'templates/admin-main.php';
    }

    public function render_departments_page() {
        global $wpdb;
        $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hd_departments");
        $users = $wpdb->get_results("SELECT id, display_name FROM {$wpdb->prefix}hd_users WHERE role IN ('hd_administrator', 'hd_department_head')");

        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $edit_item = $edit_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_departments WHERE id = %d", $edit_id)) : null;
        $settings = $edit_item ? json_decode($edit_item->settings, true) : array();

        include HD_PATH . 'templates/admin-departments.php';
    }

    public function render_categories_page() {
        global $wpdb;
        $items = $wpdb->get_results("SELECT c.*, d.name as dept_name FROM {$wpdb->prefix}hd_categories c LEFT JOIN {$wpdb->prefix}hd_departments d ON c.department_id = d.id");
        $depts = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}hd_departments");
        $users = $wpdb->get_results("SELECT id, display_name FROM {$wpdb->prefix}hd_users WHERE role IN ('hd_administrator', 'hd_executor')");

        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $edit_item = $edit_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_categories WHERE id = %d", $edit_id)) : null;

        include HD_PATH . 'templates/admin-categories.php';
    }

    public function render_settings_page() {
        include HD_PATH . 'templates/admin-settings.php';
    }

    public function render_users_page() {
        global $wpdb;
        $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hd_users");

        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $edit_item = $edit_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_users WHERE id = %d", $edit_id)) : null;

        include HD_PATH . 'templates/admin-users.php';
    }
}

new HD_Admin();
