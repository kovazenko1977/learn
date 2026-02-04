<?php

if (!defined('ABSPATH')) {
    exit;
}

class HD_API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('hd/v1', '/login', array(
            'methods' => 'POST',
            'callback' => array($this, 'login'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('hd/v1', '/requests', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_requests'),
            'permission_callback' => array($this, 'check_auth')
        ));

        register_rest_route('hd/v1', '/requests/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_request'),
            'permission_callback' => array($this, 'check_auth')
        ));

        register_rest_route('hd/v1', '/requests', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_request'),
            'permission_callback' => array($this, 'check_auth')
        ));

        register_rest_route('hd/v1', '/comments', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_comment'),
            'permission_callback' => array($this, 'check_auth')
        ));

        register_rest_route('hd/v1', '/status', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_status'),
            'permission_callback' => array($this, 'check_auth')
        ));
    }

    public function check_auth($request) {
        $token = $request->get_header('X-HD-Token');
        if (!$token) return false;

        global $wpdb;
        $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_users WHERE api_token = %s", $token));

        if ($user) {
            // Store user for the duration of the request
            $GLOBALS['hd_api_user'] = $user;
            return true;
        }
        return false;
    }

    private function get_current_user() {
        return isset($GLOBALS['hd_api_user']) ? $GLOBALS['hd_api_user'] : null;
    }

    public function login($request) {
        $params = $request->get_params();
        $username = isset($params['username']) ? $params['username'] : '';
        $password = isset($params['password']) ? $params['password'] : '';

        global $wpdb;
        $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_users WHERE username = %s", $username));

        if ($user && password_verify($password, $user->password)) {
            return array(
                'status' => 'success',
                'token' => $user->api_token,
                'user' => array(
                    'id' => $user->id,
                    'display_name' => $user->display_name,
                    'role' => $user->role
                )
            );
        }

        return new WP_Error('auth_failed', 'Invalid credentials', array('status' => 401));
    }

    public function get_requests($request) {
        global $wpdb;
        $user = $this->get_current_user();

        $query = "SELECT r.*, c.name as cat_name, d.name as dept_name FROM {$wpdb->prefix}hd_requests r
                  LEFT JOIN {$wpdb->prefix}hd_categories c ON r.category_id = c.id
                  LEFT JOIN {$wpdb->prefix}hd_departments d ON r.department_id = d.id WHERE 1=1";

        // Simple RBAC for API
        if ($user->role === 'hd_administrator') {
            // All
        } else if ($user->role === 'hd_department_head') {
            $managed_depts = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}hd_departments WHERE manager_id = %d", $user->id));
            if ($managed_depts) {
                $query .= " AND r.department_id IN (" . implode(',', array_map('intval', $managed_depts)) . ")";
            } else {
                return array();
            }
        } else if ($user->role === 'hd_executor') {
            $query .= $wpdb->prepare(" AND r.executor_id = %d", $user->id);
        } else {
            $query .= $wpdb->prepare(" AND r.responsible_id = %d", $user->id);
        }

        return $wpdb->get_results($query);
    }

    public function get_request($request) {
        global $wpdb;
        $id = $request['id'];
        $user = $this->get_current_user();

        $hd_request = $wpdb->get_row($wpdb->prepare("SELECT r.*, c.name as cat_name, d.name as dept_name FROM {$wpdb->prefix}hd_requests r
                  LEFT JOIN {$wpdb->prefix}hd_categories c ON r.category_id = c.id
                  LEFT JOIN {$wpdb->prefix}hd_departments d ON r.department_id = d.id WHERE r.id = %d", $id));

        if (!$hd_request) return new WP_Error('not_found', 'Request not found', array('status' => 404));

        // Basic permission check
        // (Simplified for now, but should ideally reuse class-hd-auth logic if possible)

        $comments = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_comments WHERE request_id = %d", $id));
        $photos = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hd_photos WHERE request_id = %d", $id));

        return array(
            'request' => $hd_request,
            'comments' => $comments,
            'photos' => $photos
        );
    }

    public function create_request($request) {
        $params = $request->get_params();
        if (empty($params['title']) || empty($params['category_id'])) {
            return new WP_Error('missing_params', 'Title and Category ID are required', array('status' => 400));
        }

        $user = $this->get_current_user();
        if ($user->role !== 'hd_administrator' && $user->role !== 'hd_responsible') {
            return new WP_Error('forbidden', 'Only responsible or admin can create requests', array('status' => 403));
        }

        // Mock current user for HD_Request_Manager if needed, but HD_Request_Manager now uses HD_Auth
        // Since HD_Auth is session-based, we need to temporarily set the session user or refactor HD_Request_Manager.
        // Actually, let's just use the manager's logic directly or temporarily mock session.
        $_SESSION['hd_user_id'] = $user->id;
        $id = HD_Request_Manager::create_request($params);

        if ($id) {
            return array('status' => 'success', 'id' => $id);
        }
        return new WP_Error('creation_failed', 'Could not create request', array('status' => 500));
    }

    public function add_comment($request) {
        $params = $request->get_params();
        if (empty($params['request_id']) || empty($params['content'])) {
            return new WP_Error('missing_params', 'Request ID and Content are required', array('status' => 400));
        }

        $user = $this->get_current_user();
        $_SESSION['hd_user_id'] = $user->id;

        if (HD_Request_Manager::add_comment($params['request_id'], $params['content'])) {
            return array('status' => 'success');
        }
        return new WP_Error('comment_failed', 'Could not add comment', array('status' => 500));
    }

    public function update_status($request) {
        $params = $request->get_params();
        if (empty($params['request_id']) || empty($params['status'])) {
            return new WP_Error('missing_params', 'Request ID and Status are required', array('status' => 400));
        }

        $user = $this->get_current_user();
        $_SESSION['hd_user_id'] = $user->id;

        if (HD_Request_Manager::update_status($params['request_id'], $params['status'])) {
            return array('status' => 'success');
        }
        return new WP_Error('update_failed', 'Could not update status', array('status' => 500));
    }
}

new HD_API();
