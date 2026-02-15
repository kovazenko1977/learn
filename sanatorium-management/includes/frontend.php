<?php
if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('san_booking_form', 'san_booking_form_shortcode');

function san_booking_form_shortcode() {
    global $wpdb;

    // Enqueue scripts and styles for the form
    wp_enqueue_style('san-frontend-css', SAN_PLUGIN_URL . 'assets/css/frontend.css', array(), '1.0.0');
    wp_enqueue_script('san-frontend-js', SAN_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), '1.0.0', true);
    wp_localize_script('san-frontend-js', 'san_ajax', array('ajax_url' => admin_url('admin-ajax.php')));

    $rooms = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}san_rooms");
    $packages = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}san_packages");
    $procedures = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}san_procedures");
    $services = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}san_services");

    $visitor_info = get_option('san_visitor_info', '');

    ob_start();
    ?>
    <div class="san-booking-form-container">
        <?php if ($visitor_info) : ?>
            <div class="san-visitor-info">
                <?php echo wpautop($visitor_info); ?>
            </div>
        <?php endif; ?>

        <form id="san-booking-form" method="post">
            <?php wp_nonce_field('san_booking_nonce', 'security'); ?>

            <div class="san-form-group">
                <label for="arrival_date"><?php _e('Дата заезда', 'sanatorium-management'); ?> *</label>
                <input type="date" name="arrival_date" id="arrival_date" required min="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="san-form-group">
                <label for="duration"><?php _e('Количество дней', 'sanatorium-management'); ?> *</label>
                <input type="number" name="duration" id="duration" value="1" min="1" required>
            </div>

            <div class="san-form-group">
                <label for="room_package"><?php _e('Выберите номер или путевку', 'sanatorium-management'); ?> *</label>
                <select name="room_package_id" id="room_package" required>
                    <option value=""><?php _e('--- Выберите ---', 'sanatorium-management'); ?></option>
                    <optgroup label="<?php _e('Номера', 'sanatorium-management'); ?>">
                        <?php foreach ($rooms as $room) : ?>
                            <option value="room_<?php echo $room->id; ?>"><?php echo esc_html($room->title); ?> - <?php echo number_format($room->cost, 2); ?> руб.</option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="<?php _e('Путевки', 'sanatorium-management'); ?>">
                        <?php foreach ($packages as $pkg) : ?>
                            <option value="pkg_<?php echo $pkg->id; ?>"><?php echo esc_html($pkg->title); ?> - <?php echo number_format($pkg->cost, 2); ?> руб.</option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>

            <div class="san-form-group">
                <label><?php _e('Дополнительные процедуры', 'sanatorium-management'); ?></label>
                <div class="san-checkbox-group">
                    <?php foreach ($procedures as $proc) : ?>
                        <label>
                            <input type="checkbox" name="extra_procedures[]" value="<?php echo $proc->id; ?>">
                            <?php echo esc_html($proc->title); ?> (+<?php echo number_format($proc->cost, 2); ?> руб.)
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="san-form-group">
                <label><?php _e('Дополнительные услуги', 'sanatorium-management'); ?></label>
                <div class="san-checkbox-group">
                    <?php foreach ($services as $svc) : ?>
                        <label>
                            <input type="checkbox" name="extra_services[]" value="<?php echo $svc->id; ?>">
                            <?php echo esc_html($svc->title); ?> (+<?php echo number_format($svc->cost, 2); ?> руб.)
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="san-form-group">
                <label for="people_count"><?php _e('Количество человек', 'sanatorium-management'); ?> *</label>
                <input type="number" name="people_count" id="people_count" value="1" min="1" required>
            </div>

            <div class="san-form-group">
                <label for="phone"><?php _e('Ваш номер телефона', 'sanatorium-management'); ?> *</label>
                <input type="tel" name="phone" id="phone" placeholder="+7 (___) ___-__-__" required>
            </div>

            <div id="san-booking-message"></div>

            <button type="submit" class="san-submit-button"><?php _e('Забронировать', 'sanatorium-management'); ?></button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
