<?php
/**
 * Standalone Security and Logic Verification Script for Helpdesk Enterprise
 */

// Mock WordPress environment
define('ABSPATH', './');
function __($text, $domain) { return $text; }
function esc_html($text) { return htmlspecialchars($text); }
function sanitize_text_field($text) { return trim($text); }
function wp_kses_post($text) { return $text; }
function current_time($type) { return date('Y-m-d H:i:s'); }
function do_action($tag, ...$args) {}
function check_ajax_referer($nonce, $query_arg) {}
function wp_send_json_success($data = null) { echo "SUCCESS: " . json_encode($data) . "\n"; }
function wp_send_json_error($data = null) { echo "ERROR: " . json_encode($data) . "\n"; }
function sanitize_textarea_field($text) { return $text; }

class MockDB {
    public $prefix = 'wp_';
    public function prepare($query, ...$args) {
        return vsprintf(str_replace('%d', '%s', $query), $args);
    }
    public function get_row($query) {
        // Mock data logic based on query content
        if (strpos($query, 'hd_requests') !== false) {
            return (object)[
                'id' => 101,
                'department_id' => 1,
                'responsible_id' => 10,
                'executor_id' => 20,
                'status' => 'new'
            ];
        }
        if (strpos($query, 'hd_users') !== false) {
            return (object)[
                'id' => 1,
                'role' => 'hd_administrator'
            ];
        }
        return null;
    }
    public function get_var($query) {
        if (strpos($query, 'hd_departments') !== false) {
            return 1; // is manager
        }
        return null;
    }
    public function get_results($query) { return []; }
    public function get_col($query) { return [1]; }
}

$GLOBALS['wpdb'] = new MockDB();

// Load the class to test
require_once dirname(__DIR__) . '/includes/class-hd-auth.php';

function assert_test($condition, $message) {
    if ($condition) {
        echo "[PASS] $message\n";
    } else {
        echo "[FAIL] $message\n";
        exit(1);
    }
}

echo "Starting tests...\n";

// Test 1: Admin Access
$admin = (object)['id' => 1, 'role' => 'hd_administrator'];
HD_Auth::set_user($admin);
assert_test(HD_Auth::current_user_can('hd_view_all'), "Admin can view all");
assert_test(HD_Auth::can_access_request(101), "Admin can access any request");
assert_test(HD_Auth::can_manage_request(101), "Admin can manage any request");

// Test 2: Responsible Access
$resp = (object)['id' => 10, 'role' => 'hd_responsible'];
HD_Auth::set_user($resp);
assert_test(HD_Auth::can_access_request(101), "Responsible can access their own request");
assert_test(!HD_Auth::can_manage_request(101), "Responsible cannot manage request");

// Test 3: Other user access denial
$other = (object)['id' => 99, 'role' => 'hd_responsible'];
HD_Auth::set_user($other);
assert_test(!HD_Auth::can_access_request(101), "Other user cannot access non-owned request");

// Test 4: Executor Access
$exec = (object)['id' => 20, 'role' => 'hd_executor'];
HD_Auth::set_user($exec);
assert_test(HD_Auth::can_access_request(101), "Executor can access assigned request");
assert_test(HD_Auth::can_manage_request(101), "Executor can manage (status) assigned request");

// Test 5: Dept Head Access
$mgr = (object)['id' => 5, 'role' => 'hd_department_head'];
HD_Auth::set_user($mgr);
// Mock logic in MockDB handles manager check
assert_test(HD_Auth::can_access_request(101), "Dept Head can access request in their dept");
assert_test(HD_Auth::can_manage_request(101), "Dept Head can manage request in their dept");

echo "All tests passed successfully!\n";
