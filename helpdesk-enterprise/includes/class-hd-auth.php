<?php

if (!defined('ABSPATH')) {
    exit;
}

class HD_Auth {
    private static $user = null;

    public static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set_user($user) {
        self::$user = $user;
    }

    public static function login($username, $password) {
        global $wpdb;
        $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_users WHERE username = %s", $username));

        if ($user && password_verify($password, $user->password)) {
            $_SESSION['hd_user_id'] = $user->id;
            self::$user = $user;
            return true;
        }
        return false;
    }

    public static function logout() {
        unset($_SESSION['hd_user_id']);
        self::$user = null;
    }

    public static function get_user() {
        if (self::$user !== null) {
            return self::$user;
        }

        if (isset($_SESSION['hd_user_id'])) {
            global $wpdb;
            self::$user = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_users WHERE id = %d", $_SESSION['hd_user_id']));
            return self::$user;
        }

        return null;
    }

    public static function is_logged_in() {
        return self::get_user() !== null;
    }

    public static function get_user_id() {
        $user = self::get_user();
        return $user ? $user->id : 0;
    }

    public static function get_user_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_users WHERE id = %d", $id));
    }

    public static function can_access_request($request_id) {
        global $wpdb;
        $request = $wpdb->get_row($wpdb->prepare("SELECT department_id, responsible_id, executor_id FROM {$wpdb->prefix}hd_requests WHERE id = %d", $request_id));
        if (!$request) return false;

        $user_id = self::get_user_id();
        if (self::current_user_can('hd_view_all')) return true;

        if (self::current_user_can('hd_view_dept')) {
            $is_manager = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}hd_departments WHERE id = %d AND manager_id = %d", $request->department_id, $user_id));
            if ($is_manager) return true;
        }

        if ($user_id == $request->responsible_id || $user_id == $request->executor_id) return true;

        return false;
    }

    public static function can_manage_request($request_id) {
        global $wpdb;
        $request = $wpdb->get_row($wpdb->prepare("SELECT department_id, executor_id FROM {$wpdb->prefix}hd_requests WHERE id = %d", $request_id));
        if (!$request) return false;

        $user_id = self::get_user_id();
        if (self::current_user_can('hd_manage_all')) return true;

        if (self::current_user_can('hd_manage_dept')) {
            $is_manager = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}hd_departments WHERE id = %d AND manager_id = %d", $request->department_id, $user_id));
            if ($is_manager) return true;
        }

        if (self::current_user_can('hd_update_status') && $user_id == $request->executor_id) return true;

        return false;
    }

    public static function current_user_can($capability) {
        $user = self::get_user();
        if (!$user) return false;

        $role_caps = array(
            'hd_administrator' => array('hd_view_all', 'hd_manage_all', 'hd_delete_data', 'hd_manage_settings', 'hd_create_requests', 'hd_view_own', 'hd_update_status', 'hd_add_comments', 'hd_admin_shortcodes'),
            'hd_department_head' => array('hd_view_dept', 'hd_manage_dept', 'hd_export_dept', 'hd_update_status', 'hd_add_comments'),
            'hd_executor' => array('hd_view_assigned', 'hd_update_status', 'hd_add_comments'),
            'hd_responsible' => array('hd_create_requests', 'hd_view_own', 'hd_add_comments')
        );

        $caps = isset($role_caps[$user->role]) ? $role_caps[$user->role] : array();

        // Map specific caps if needed or just use the list
        if ($user->role === 'hd_administrator') return true;

        return in_array($capability, $caps);
    }
}
