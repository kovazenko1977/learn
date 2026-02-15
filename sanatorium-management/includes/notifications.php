<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('san_after_booking_created', 'san_send_admin_notifications');

function san_send_admin_notifications($booking_id) {
    global $wpdb;

    $booking = $wpdb->get_row($wpdb->prepare(
        "SELECT b.*, r.title as room_title FROM {$wpdb->prefix}san_bookings b
         JOIN {$wpdb->prefix}san_rooms r ON b.room_id = r.id
         WHERE b.id = %d",
        $booking_id
    ));

    if (!$booking) return;

    $admin_email = get_option('san_admin_email', get_option('admin_email'));
    $subject = __('Новое бронирование в Санатории', 'sanatorium-management');

    $message = sprintf(__('Новое бронирование #%d', 'sanatorium-management'), $booking_id) . "\n";
    $message .= sprintf(__('Дата заезда: %s', 'sanatorium-management'), $booking->arrival_date) . "\n";
    $message .= sprintf(__('Количество дней: %d', 'sanatorium-management'), $booking->duration) . "\n";
    $message .= sprintf(__('Номер: %s', 'sanatorium-management'), $booking->room_title) . "\n";
    $message .= sprintf(__('Кол-во человек: %d', 'sanatorium-management'), $booking->people_count) . "\n";
    $message .= sprintf(__('Телефон: %s', 'sanatorium-management'), $booking->phone) . "\n";

    if ($booking->package_id) {
        $package = $wpdb->get_row($wpdb->prepare("SELECT title FROM {$wpdb->prefix}san_packages WHERE id = %d", $booking->package_id));
        if ($package) {
            $message .= sprintf(__('Путевка: %s', 'sanatorium-management'), $package->title) . "\n";
        }
    }

    if ($booking->procedures) {
        $proc_ids = explode(',', $booking->procedures);
        $procs = $wpdb->get_col("SELECT title FROM {$wpdb->prefix}san_procedures WHERE id IN (" . implode(',', array_map('intval', $proc_ids)) . ")");
        $message .= sprintf(__('Доп. процедуры: %s', 'sanatorium-management'), implode(', ', $procs)) . "\n";
    }

    if ($booking->services) {
        $svc_ids = explode(',', $booking->services);
        $svcs = $wpdb->get_col("SELECT title FROM {$wpdb->prefix}san_services WHERE id IN (" . implode(',', array_map('intval', $svc_ids)) . ")");
        $message .= sprintf(__('Доп. услуги: %s', 'sanatorium-management'), implode(', ', $svcs)) . "\n";
    }

    wp_mail($admin_email, $subject, $message);

    // Telegram notification will be called here as well or via another hook
    san_send_telegram_notification($message);
}

function san_send_telegram_notification($message) {
    // Integration with WP Telegram plugin
    // Usually WP Telegram sends messages when wp_mail is called if configured,
    // but the request specifically asked to integrate with it.
    // If "WP Telegram" plugin is active, we can use its action hook if we find it.
    // Common hook for WP Telegram: 'wp_telegram_send_message'

    do_action('wp_telegram_send_message', array(
        'text' => $message,
    ));
}
