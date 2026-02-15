<?php
if (!defined('ABSPATH')) {
    exit;
}

function san_create_db_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Rooms table
    $table_rooms = $wpdb->prefix . 'san_rooms';
    $sql_rooms = "CREATE TABLE $table_rooms (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        room_class varchar(100) NOT NULL,
        cost decimal(10,2) NOT NULL DEFAULT '0.00',
        total_count int(11) NOT NULL DEFAULT 1,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_rooms);

    // Procedures table
    $table_procedures = $wpdb->prefix . 'san_procedures';
    $sql_procedures = "CREATE TABLE $table_procedures (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        cost decimal(10,2) NOT NULL DEFAULT '0.00',
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_procedures);

    // Extra Services table
    $table_services = $wpdb->prefix . 'san_services';
    $sql_services = "CREATE TABLE $table_services (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        cost decimal(10,2) NOT NULL DEFAULT '0.00',
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_services);

    // Packages table
    $table_packages = $wpdb->prefix . 'san_packages';
    $sql_packages = "CREATE TABLE $table_packages (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        room_id mediumint(9) NOT NULL,
        procedure_ids text NOT NULL,
        cost decimal(10,2) NOT NULL DEFAULT '0.00',
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_packages);

    // Bookings table
    $table_bookings = $wpdb->prefix . 'san_bookings';
    $sql_bookings = "CREATE TABLE $table_bookings (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        arrival_date date NOT NULL,
        duration int(11) NOT NULL DEFAULT 1,
        room_id mediumint(9) NOT NULL,
        package_id mediumint(9) DEFAULT NULL,
        people_count int(11) NOT NULL DEFAULT 1,
        phone varchar(50) NOT NULL,
        procedures text NOT NULL,
        services text NOT NULL,
        status varchar(50) NOT NULL DEFAULT 'new',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_bookings);

    // Room Availability / Overrides table
    $table_availability = $wpdb->prefix . 'san_availability';
    $sql_availability = "CREATE TABLE $table_availability (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        room_id mediumint(9) NOT NULL,
        status_date date NOT NULL,
        status varchar(50) NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY room_date (room_id, status_date)
    ) $charset_collate;";
    dbDelta($sql_availability);
}

function san_save_room($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'san_rooms';
    $id = isset($data['id']) ? intval($data['id']) : 0;

    $room_data = array(
        'title' => sanitize_text_field($data['title']),
        'room_class' => sanitize_text_field($data['room_class']),
        'cost' => floatval($data['cost']),
        'total_count' => intval($data['total_count']),
    );

    if ($id) {
        $wpdb->update($table_name, $room_data, array('id' => $id));
    } else {
        $wpdb->insert($table_name, $room_data);
    }
}

function san_delete_room($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'san_rooms';
    $wpdb->delete($table_name, array('id' => intval($id)));
}

function san_save_procedure($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'san_procedures';
    $id = isset($data['id']) ? intval($data['id']) : 0;

    $procedure_data = array(
        'title' => sanitize_text_field($data['title']),
        'cost' => floatval($data['cost']),
    );

    if ($id) {
        $wpdb->update($table_name, $procedure_data, array('id' => $id));
    } else {
        $wpdb->insert($table_name, $procedure_data);
    }
}

function san_delete_procedure($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'san_procedures';
    $wpdb->delete($table_name, array('id' => intval($id)));
}

function san_save_service($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'san_services';
    $id = isset($data['id']) ? intval($data['id']) : 0;

    $service_data = array(
        'title' => sanitize_text_field($data['title']),
        'cost' => floatval($data['cost']),
    );

    if ($id) {
        $wpdb->update($table_name, $service_data, array('id' => $id));
    } else {
        $wpdb->insert($table_name, $service_data);
    }
}

function san_delete_service($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'san_services';
    $wpdb->delete($table_name, array('id' => intval($id)));
}

function san_save_package($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'san_packages';
    $id = isset($data['id']) ? intval($data['id']) : 0;

    $procedure_ids = isset($data['procedure_ids']) ? implode(',', array_map('intval', $data['procedure_ids'])) : '';

    $package_data = array(
        'title' => sanitize_text_field($data['title']),
        'room_id' => intval($data['room_id']),
        'procedure_ids' => $procedure_ids,
        'cost' => floatval($data['cost']),
    );

    if ($id) {
        $wpdb->update($table_name, $package_data, array('id' => $id));
    } else {
        $wpdb->insert($table_name, $package_data);
    }
}

function san_delete_package($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'san_packages';
    $wpdb->delete($table_name, array('id' => intval($id)));
}

function san_update_booking_status($id, $status) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'san_bookings';
    $wpdb->update($table_name, array('status' => sanitize_text_field($status)), array('id' => intval($id)));
}

function san_delete_booking($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'san_bookings';
    $wpdb->delete($table_name, array('id' => intval($id)));
}

function san_update_availability_override($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'san_availability';

    $room_id = intval($data['room_id']);
    $status_date = sanitize_text_field($data['status_date']);
    $status = sanitize_text_field($data['status']);

    if ($status == 'free') {
        // If set to free, we just delete the override so it falls back to booking-based status
        $wpdb->delete($table_name, array('room_id' => $room_id, 'status_date' => $status_date));
    } else {
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table_name (room_id, status_date, status)
             VALUES (%d, %s, %s)
             ON DUPLICATE KEY UPDATE status = VALUES(status)",
            $room_id, $status_date, $status
        ));
    }
}
