<?php

if (!defined('ABSPATH')) {
    exit;
}

class HD_Export {
    public function __construct() {
        add_action('wp_ajax_hd_export_requests', array($this, 'handle_export'));
    }

    public function handle_export() {
        if (!current_user_can('hd_export_dept') && !current_user_can('hd_manage_all')) {
            wp_die(__('У вас нет прав для экспорта.', 'helpdesk-enterprise'));
        }

        global $wpdb;
        $user_id = get_current_user_id();

        $query = "SELECT r.*, c.name as cat_name, d.name as dept_name FROM {$wpdb->prefix}hd_requests r
                  LEFT JOIN {$wpdb->prefix}hd_categories c ON r.category_id = c.id
                  LEFT JOIN {$wpdb->prefix}hd_departments d ON r.department_id = d.id WHERE 1=1";

        if (!current_user_can('hd_manage_all')) {
            $managed_depts = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}hd_departments WHERE manager_id = %d", $user_id));
            if ($managed_depts) {
                $query .= " AND r.department_id IN (" . implode(',', array_map('intval', $managed_depts)) . ")";
            } else {
                wp_die(__('Нет доступных отделов для экспорта.', 'helpdesk-enterprise'));
            }
        }

        $requests = $wpdb->get_results($query, ARRAY_A);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=requests_export_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
        fputcsv($output, array('ID', 'Заголовок', 'Статус', 'Категория', 'Отдел', 'Создана', 'Срок (SLA)', 'Выполнена'));

        foreach ($requests as $row) {
            fputcsv($output, array(
                $row['id'],
                $row['title'],
                $row['status'],
                $row['cat_name'],
                $row['dept_name'],
                $row['created_at'],
                $row['deadline'],
                $row['completed_at']
            ));
        }
        fclose($output);
        exit;
    }
}

new HD_Export();
