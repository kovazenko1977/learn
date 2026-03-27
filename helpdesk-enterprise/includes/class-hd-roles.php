<?php

if (!defined('ABSPATH')) {
    exit;
}

class HD_Roles {
    public static function init() {
        add_role('hd_administrator', __('Администратор Helpdesk', 'helpdesk-enterprise'), array(
            'read' => true,
            'hd_view_all_requests' => true,
            'hd_manage_all' => true,
            'hd_delete_data' => true,
            'hd_manage_settings' => true,
        ));

        add_role('hd_department_head', __('Руководитель отдела', 'helpdesk-enterprise'), array(
            'read' => true,
            'hd_view_dept_requests' => true,
            'hd_manage_dept_requests' => true,
            'hd_export_dept' => true,
        ));

        add_role('hd_executor', __('Исполнитель', 'helpdesk-enterprise'), array(
            'read' => true,
            'hd_view_own_assigned_requests' => true,
            'hd_update_status' => true,
            'hd_add_comments' => true,
            'hd_add_photos' => true,
        ));

        add_role('hd_responsible', __('Ответственный сотрудник', 'helpdesk-enterprise'), array(
            'read' => true,
            'hd_create_requests' => true,
            'hd_view_own_requests' => true,
            'hd_add_comments' => true,
            'hd_add_photos' => true,
        ));

        // Add caps to administrator
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('hd_view_all_requests');
            $admin->add_cap('hd_manage_all');
            $admin->add_cap('hd_delete_data');
            $admin->add_cap('hd_manage_settings');
        }
    }
}
