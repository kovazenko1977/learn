<?php

if (!defined('ABSPATH')) {
    exit;
}

class HD_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menus'));
        add_action('admin_init', array($this, 'handle_actions'));
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
            'users.php'
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
                wp_redirect(admin_url('admin.php?page=hd-departments&message=saved'));
                exit;

            case 'delete_department':
                $id = intval($_POST['id']);
                $wpdb->delete("{$wpdb->prefix}hd_departments", array('id' => $id));
                wp_redirect(admin_url('admin.php?page=hd-departments&message=deleted'));
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
                wp_redirect(admin_url('admin.php?page=hd-categories&message=saved'));
                exit;

            case 'save_settings':
                update_option('hd_company_name', sanitize_text_field($_POST['company_name']));
                update_option('hd_telegram_token', sanitize_text_field($_POST['telegram_token']));
                update_option('hd_telegram_bot_name', sanitize_text_field($_POST['telegram_bot_name']));
                update_option('hd_telegram_admin_chat_id', sanitize_text_field($_POST['telegram_admin_chat_id']));
                update_option('hd_default_sla', intval($_POST['default_sla']));
                update_option('hd_notify_on_create', isset($_POST['notify_on_create']) ? 1 : 0);
                update_option('hd_notify_on_status', isset($_POST['notify_on_status']) ? 1 : 0);
                wp_redirect(admin_url('admin.php?page=hd-settings&message=saved'));
                exit;
        }
    }

    public function render_main_page() {
        echo '<div class="wrap"><h1>' . __('Helpdesk Enterprise', 'helpdesk-enterprise') . '</h1><p>' . __('Добро пожаловать в корпоративную систему управления заявками.', 'helpdesk-enterprise') . '</p></div>';
    }

    public function render_departments_page() {
        global $wpdb;
        $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hd_departments");
        $users = get_users();

        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $edit_item = $edit_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_departments WHERE id = %d", $edit_id)) : null;
        $settings = $edit_item ? json_decode($edit_item->settings, true) : array();

        include HD_PATH . 'templates/admin-departments.php';
    }

    public function render_categories_page() {
        global $wpdb;
        $items = $wpdb->get_results("SELECT c.*, d.name as dept_name FROM {$wpdb->prefix}hd_categories c LEFT JOIN {$wpdb->prefix}hd_departments d ON c.department_id = d.id");
        $depts = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}hd_departments");
        $users = get_users();

        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $edit_item = $edit_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_categories WHERE id = %d", $edit_id)) : null;

        include HD_PATH . 'templates/admin-categories.php';
    }

    public function render_settings_page() {
        include HD_PATH . 'templates/admin-settings.php';
    }
}

new HD_Admin();
