<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_san_submit_booking', 'san_handle_booking_submission');
add_action('wp_ajax_nopriv_san_submit_booking', 'san_handle_booking_submission');

function san_handle_booking_submission() {
    check_ajax_referer('san_booking_nonce', 'security');

    global $wpdb;

    $arrival_date = sanitize_text_field($_POST['arrival_date']);
    $duration = intval($_POST['duration']);
    $room_package_id = sanitize_text_field($_POST['room_package_id']);
    $people_count = intval($_POST['people_count']);
    $phone = sanitize_text_field($_POST['phone']);
    $extra_procedures = isset($_POST['extra_procedures']) ? array_map('intval', $_POST['extra_procedures']) : array();
    $extra_services = isset($_POST['extra_services']) ? array_map('intval', $_POST['extra_services']) : array();

    if (empty($arrival_date) || empty($room_package_id) || empty($phone)) {
        wp_send_json_error(array('message' => __('Пожалуйста, заполните все обязательные поля', 'sanatorium-management')));
    }

    $room_id = 0;
    $package_id = NULL;

    if (strpos($room_package_id, 'room_') === 0) {
        $room_id = intval(str_replace('room_', '', $room_package_id));
    } elseif (strpos($room_package_id, 'pkg_') === 0) {
        $package_id = intval(str_replace('pkg_', '', $room_package_id));
        $package = $wpdb->get_row($wpdb->prepare("SELECT room_id FROM {$wpdb->prefix}san_packages WHERE id = %d", $package_id));
        if ($package) {
            $room_id = $package->room_id;
        }
    }

    if (!$room_id) {
        wp_send_json_error(array('message' => __('Некорректный выбор номера или путевки', 'sanatorium-management')));
    }

    $table_bookings = $wpdb->prefix . 'san_bookings';
    $data = array(
        'arrival_date' => $arrival_date,
        'duration' => $duration,
        'room_id' => $room_id,
        'package_id' => $package_id,
        'people_count' => $people_count,
        'phone' => $phone,
        'procedures' => implode(',', $extra_procedures),
        'services' => implode(',', $extra_services),
        'status' => 'new'
    );

    $result = $wpdb->insert($table_bookings, $data);

    if ($result) {
        $booking_id = $wpdb->insert_id;

        // Trigger notifications
        do_action('san_after_booking_created', $booking_id);

        wp_send_json_success(array(
            'message' => __('Спасибо! Ваша заявка принята. Мы свяжемся с вами в ближайшее время.', 'sanatorium-management'),
            'booking_id' => $booking_id
        ));
    } else {
        wp_send_json_error(array('message' => __('Ошибка при сохранении данных. Попробуйте позже.', 'sanatorium-management')));
    }
}
