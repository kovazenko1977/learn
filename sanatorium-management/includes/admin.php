<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'san_add_admin_menu');
add_action('admin_init', 'san_handle_admin_actions');

function san_handle_admin_actions() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'sanatorium-management') {
        return;
    }

    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($tab == 'rooms') {
        if ($action == 'save' && isset($_POST['submit']) && check_admin_referer('san_room_action', 'san_room_nonce')) {
            san_save_room($_POST);
            wp_redirect(admin_url('admin.php?page=sanatorium-management&tab=rooms'));
            exit;
        }
        if ($action == 'delete' && isset($_GET['id'])) {
            san_delete_room($_GET['id']);
            wp_redirect(admin_url('admin.php?page=sanatorium-management&tab=rooms'));
            exit;
        }
    }

    if ($tab == 'procedures') {
        if ($action == 'save' && isset($_POST['submit']) && check_admin_referer('san_procedure_action', 'san_procedure_nonce')) {
            san_save_procedure($_POST);
            wp_redirect(admin_url('admin.php?page=sanatorium-management&tab=procedures'));
            exit;
        }
        if ($action == 'delete' && isset($_GET['id'])) {
            san_delete_procedure($_GET['id']);
            wp_redirect(admin_url('admin.php?page=sanatorium-management&tab=procedures'));
            exit;
        }
    }

    if ($tab == 'services') {
        if ($action == 'save' && isset($_POST['submit']) && check_admin_referer('san_service_action', 'san_service_nonce')) {
            san_save_service($_POST);
            wp_redirect(admin_url('admin.php?page=sanatorium-management&tab=services'));
            exit;
        }
        if ($action == 'delete' && isset($_GET['id'])) {
            san_delete_service($_GET['id']);
            wp_redirect(admin_url('admin.php?page=sanatorium-management&tab=services'));
            exit;
        }
    }

    if ($tab == 'packages') {
        if ($action == 'save' && isset($_POST['submit']) && check_admin_referer('san_package_action', 'san_package_nonce')) {
            san_save_package($_POST);
            wp_redirect(admin_url('admin.php?page=sanatorium-management&tab=packages'));
            exit;
        }
        if ($action == 'delete' && isset($_GET['id'])) {
            san_delete_package($_GET['id']);
            wp_redirect(admin_url('admin.php?page=sanatorium-management&tab=packages'));
            exit;
        }
    }

    if ($tab == 'bookings') {
        if ($action == 'update_status' && isset($_POST['id']) && check_admin_referer('san_booking_status_action', 'san_booking_status_nonce')) {
            san_update_booking_status($_POST['id'], $_POST['status']);
            wp_redirect(admin_url('admin.php?page=sanatorium-management&tab=bookings'));
            exit;
        }
        if ($action == 'delete' && isset($_GET['id'])) {
            san_delete_booking($_GET['id']);
            wp_redirect(admin_url('admin.php?page=sanatorium-management&tab=bookings'));
            exit;
        }
    }

    if ($tab == 'availability') {
        if ($action == 'update_override' && isset($_POST['room_id']) && check_admin_referer('san_override_action', 'san_override_nonce')) {
            san_update_availability_override($_POST);
            wp_redirect(admin_url('admin.php?page=sanatorium-management&tab=availability&start_date=' . sanitize_text_field($_POST['status_date'])));
            exit;
        }
    }
}

function san_add_admin_menu() {
    add_menu_page(
        __('Санаторий', 'sanatorium-management'),
        __('Санаторий', 'sanatorium-management'),
        'manage_options',
        'sanatorium-management',
        'san_admin_page_display',
        'dashicons-building',
        30
    );
}

function san_admin_page_display() {
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
    ?>
    <div class="wrap">
        <h1><?php _e('Управление Санаторием', 'sanatorium-management'); ?></h1>

        <h2 class="nav-tab-wrapper">
            <a href="?page=sanatorium-management&tab=dashboard" class="nav-tab <?php echo $active_tab == 'dashboard' ? 'nav-tab-active' : ''; ?>"><?php _e('Дашборд', 'sanatorium-management'); ?></a>
            <a href="?page=sanatorium-management&tab=bookings" class="nav-tab <?php echo $active_tab == 'bookings' ? 'nav-tab-active' : ''; ?>"><?php _e('Бронирования', 'sanatorium-management'); ?></a>
            <a href="?page=sanatorium-management&tab=availability" class="nav-tab <?php echo $active_tab == 'availability' ? 'nav-tab-active' : ''; ?>"><?php _e('Наличие мест', 'sanatorium-management'); ?></a>
            <a href="?page=sanatorium-management&tab=rooms" class="nav-tab <?php echo $active_tab == 'rooms' ? 'nav-tab-active' : ''; ?>"><?php _e('Номера', 'sanatorium-management'); ?></a>
            <a href="?page=sanatorium-management&tab=procedures" class="nav-tab <?php echo $active_tab == 'procedures' ? 'nav-tab-active' : ''; ?>"><?php _e('Процедуры', 'sanatorium-management'); ?></a>
            <a href="?page=sanatorium-management&tab=services" class="nav-tab <?php echo $active_tab == 'services' ? 'nav-tab-active' : ''; ?>"><?php _e('Доп. услуги', 'sanatorium-management'); ?></a>
            <a href="?page=sanatorium-management&tab=packages" class="nav-tab <?php echo $active_tab == 'packages' ? 'nav-tab-active' : ''; ?>"><?php _e('Путевки', 'sanatorium-management'); ?></a>
            <a href="?page=sanatorium-management&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php _e('Настройки', 'sanatorium-management'); ?></a>
        </h2>

        <div class="san-content card">
            <?php
            switch ($active_tab) {
                case 'dashboard':
                    san_render_dashboard();
                    break;
                case 'bookings':
                    san_render_bookings();
                    break;
                case 'availability':
                    san_render_availability();
                    break;
                case 'rooms':
                    san_render_rooms();
                    break;
                case 'procedures':
                    san_render_procedures();
                    break;
                case 'services':
                    san_render_services();
                    break;
                case 'packages':
                    san_render_packages();
                    break;
                case 'settings':
                    san_render_settings();
                    break;
                default:
                    san_render_dashboard();
                    break;
            }
            ?>
        </div>
    </div>
    <?php
}

// Placeholder functions to avoid errors until implemented
if (!function_exists('san_render_dashboard')) {
    function san_render_dashboard() {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'san_bookings';
        $rooms_table = $wpdb->prefix . 'san_rooms';

        $total_bookings = $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table");
        $new_bookings = $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table WHERE status = 'new'");
        $occupied_today = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings_table WHERE status = 'occupied' AND arrival_date = %s",
            date('Y-m-d')
        ));

        $revenue = $wpdb->get_var("SELECT SUM(r.cost) FROM $bookings_table b JOIN $rooms_table r ON b.room_id = r.id WHERE b.status IN ('occupied', 'completed')");
        ?>
        <h3><?php _e('Дашборд', 'sanatorium-management'); ?></h3>

        <div class="san-dashboard-widgets" style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
            <div class="san-widget card" style="flex: 1; min-width: 200px; padding: 20px; border: 1px solid #ccd0d4; background: #fff;">
                <h4><?php _e('Всего бронирований', 'sanatorium-management'); ?></h4>
                <div class="san-widget-value" style="font-size: 24px; font-weight: bold;"><?php echo (int)$total_bookings; ?></div>
            </div>
            <div class="san-widget card" style="flex: 1; min-width: 200px; padding: 20px; border: 1px solid #ccd0d4; background: #fff;">
                <h4><?php _e('Новые заявки', 'sanatorium-management'); ?></h4>
                <div class="san-widget-value" style="font-size: 24px; font-weight: bold; color: #d63638;"><?php echo (int)$new_bookings; ?></div>
            </div>
            <div class="san-widget card" style="flex: 1; min-width: 200px; padding: 20px; border: 1px solid #ccd0d4; background: #fff;">
                <h4><?php _e('Занято сегодня', 'sanatorium-management'); ?></h4>
                <div class="san-widget-value" style="font-size: 24px; font-weight: bold;"><?php echo (int)$occupied_today; ?></div>
            </div>
            <div class="san-widget card" style="flex: 1; min-width: 200px; padding: 20px; border: 1px solid #ccd0d4; background: #fff;">
                <h4><?php _e('Выручка (расчетная)', 'sanatorium-management'); ?></h4>
                <div class="san-widget-value" style="font-size: 24px; font-weight: bold; color: #46b450;"><?php echo number_format((float)$revenue, 2); ?></div>
            </div>
        </div>

        <div style="margin-top: 30px;">
            <h4><?php _e('Последние бронирования', 'sanatorium-management'); ?></h4>
            <?php
            $latest_bookings = $wpdb->get_results("SELECT b.*, r.title as room_title FROM $bookings_table b JOIN $rooms_table r ON b.room_id = r.id ORDER BY b.id DESC LIMIT 5");
            if ($latest_bookings) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'sanatorium-management'); ?></th>
                            <th><?php _e('Дата', 'sanatorium-management'); ?></th>
                            <th><?php _e('Номер', 'sanatorium-management'); ?></th>
                            <th><?php _e('Телефон', 'sanatorium-management'); ?></th>
                            <th><?php _e('Статус', 'sanatorium-management'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latest_bookings as $b) : ?>
                            <tr>
                                <td><?php echo $b->id; ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($b->arrival_date)); ?></td>
                                <td><?php echo esc_html($b->room_title); ?></td>
                                <td><?php echo esc_html($b->phone); ?></td>
                                <td><?php echo esc_html($b->status); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('Нет бронирований', 'sanatorium-management'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}
if (!function_exists('san_render_bookings')) {
    function san_render_bookings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'san_bookings';
        $rooms_table = $wpdb->prefix . 'san_rooms';
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';

        $query = "SELECT b.*, r.title as room_title FROM $table_name b LEFT JOIN $rooms_table r ON b.room_id = r.id";
        if ($status_filter) {
            $query .= $wpdb->prepare(" WHERE b.status = %s", $status_filter);
        }
        $query .= " ORDER BY b.id DESC";
        $items = $wpdb->get_results($query);

        $statuses = array(
            'new' => __('Новый', 'sanatorium-management'),
            'reserved' => __('Забронировано', 'sanatorium-management'),
            'occupied' => __('Занято', 'sanatorium-management'),
            'completed' => __('Завершено', 'sanatorium-management'),
            'cancelled' => __('Отменено', 'sanatorium-management')
        );
        ?>
        <h3><?php _e('Бронирования', 'sanatorium-management'); ?></h3>

        <div class="tablenav top">
            <div class="alignleft actions">
                <form method="get" action="">
                    <input type="hidden" name="page" value="sanatorium-management">
                    <input type="hidden" name="tab" value="bookings">
                    <select name="status_filter">
                        <option value=""><?php _e('Все статусы', 'sanatorium-management'); ?></option>
                        <?php foreach ($statuses as $val => $label) : ?>
                            <option value="<?php echo $val; ?>" <?php selected($status_filter, $val); ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" class="button" value="<?php _e('Фильтр', 'sanatorium-management'); ?>">
                </form>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'sanatorium-management'); ?></th>
                    <th><?php _e('Дата заезда', 'sanatorium-management'); ?></th>
                    <th><?php _e('Дней', 'sanatorium-management'); ?></th>
                    <th><?php _e('Номер', 'sanatorium-management'); ?></th>
                    <th><?php _e('Человек', 'sanatorium-management'); ?></th>
                    <th><?php _e('Телефон', 'sanatorium-management'); ?></th>
                    <th><?php _e('Статус', 'sanatorium-management'); ?></th>
                    <th><?php _e('Действия', 'sanatorium-management'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items) : foreach ($items as $item) : ?>
                    <tr>
                        <td><?php echo $item->id; ?></td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($item->arrival_date)); ?></td>
                        <td><?php echo $item->duration; ?></td>
                        <td><?php echo esc_html($item->room_title); ?></td>
                        <td><?php echo $item->people_count; ?></td>
                        <td><?php echo esc_html($item->phone); ?></td>
                        <td>
                            <form method="post" action="?page=sanatorium-management&tab=bookings&action=update_status" style="display:inline;">
                                <?php wp_nonce_field('san_booking_status_action', 'san_booking_status_nonce'); ?>
                                <input type="hidden" name="id" value="<?php echo $item->id; ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <?php foreach ($statuses as $val => $label) : ?>
                                        <option value="<?php echo $val; ?>" <?php selected($item->status, $val); ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td>
                            <a href="?page=sanatorium-management&tab=bookings&action=delete&id=<?php echo $item->id; ?>" onclick="return confirm('<?php _e('Вы уверены?', 'sanatorium-management'); ?>')"><?php _e('Удалить', 'sanatorium-management'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="7"><?php _e('Бронирования не найдены', 'sanatorium-management'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}
if (!function_exists('san_render_availability')) {
    function san_render_availability() {
        global $wpdb;
        $rooms_table = $wpdb->prefix . 'san_rooms';
        $bookings_table = $wpdb->prefix . 'san_bookings';
        $availability_table = $wpdb->prefix . 'san_availability';

        $rooms = $wpdb->get_results("SELECT id, title FROM $rooms_table");

        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d');
        $days = 14; // Show 2 weeks

        $dates = array();
        for ($i = 0; $i < $days; $i++) {
            $dates[] = date('Y-m-d', strtotime("$start_date + $i days"));
        }

        // Fetch manual overrides
        $overrides = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $availability_table WHERE status_date >= %s AND status_date <= %s",
            $dates[0], end($dates)
        ));
        $override_map = array();
        foreach ($overrides as $o) {
            $override_map[$o->room_id][$o->status_date] = $o->status;
        }

        // Fetch bookings
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT id, room_id, arrival_date, status FROM $bookings_table WHERE arrival_date >= %s AND arrival_date <= %s",
            $dates[0], end($dates)
        ));
        $booking_map = array();
        foreach ($bookings as $b) {
            $booking_map[$b->room_id][$b->arrival_date][] = $b->status;
        }

        $statuses = array(
            'free' => __('Свободно', 'sanatorium-management'),
            'reserved' => __('Забронировано', 'sanatorium-management'),
            'occupied' => __('Занято', 'sanatorium-management')
        );

        ?>
        <h3><?php _e('Наличие мест и управление статусами', 'sanatorium-management'); ?></h3>

        <form method="get" action="">
            <input type="hidden" name="page" value="sanatorium-management">
            <input type="hidden" name="tab" value="availability">
            <label for="start_date"><?php _e('Дата начала:', 'sanatorium-management'); ?></label>
            <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($start_date); ?>">
            <input type="submit" class="button" value="<?php _e('Показать', 'sanatorium-management'); ?>">
        </form>

        <br>

        <table class="wp-list-table widefat fixed striped san-availability-grid" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="width: 150px;"><?php _e('Номер', 'sanatorium-management'); ?></th>
                    <?php foreach ($dates as $date) : ?>
                        <th style="text-align: center; font-size: 10px;">
                            <?php echo date_i18n('d.m', strtotime($date)); ?><br>
                            <?php echo date_i18n('D', strtotime($date)); ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($room->title); ?></strong></td>
                        <?php foreach ($dates as $date) :
                            $status = 'free';
                            $class = 'san-status-free';

                            if (isset($override_map[$room->id][$date])) {
                                $status = $override_map[$room->id][$date];
                            } elseif (isset($booking_map[$room->id][$date])) {
                                // Simple logic: if any booking is reserved/occupied, show it
                                if (in_array('occupied', $booking_map[$room->id][$date])) $status = 'occupied';
                                elseif (in_array('reserved', $booking_map[$room->id][$date])) $status = 'reserved';
                                elseif (in_array('new', $booking_map[$room->id][$date])) $status = 'reserved';
                            }

                            $class = 'san-status-' . $status;
                            ?>
                            <td class="<?php echo $class; ?>" style="text-align: center; border: 1px solid #ccc; padding: 5px;">
                                <form method="post" action="?page=sanatorium-management&tab=availability&action=update_override">
                                    <?php wp_nonce_field('san_override_action', 'san_override_nonce'); ?>
                                    <input type="hidden" name="room_id" value="<?php echo $room->id; ?>">
                                    <input type="hidden" name="status_date" value="<?php echo $date; ?>">
                                    <select name="status" onchange="this.form.submit()" style="font-size: 10px; width: 100%; padding: 0;">
                                        <?php foreach ($statuses as $val => $label) : ?>
                                            <option value="<?php echo $val; ?>" <?php selected($status, $val); ?>><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <style>
            .san-status-free { background-color: #e7f9ed !important; }
            .san-status-reserved { background-color: #fff9e7 !important; }
            .san-status-occupied { background-color: #fce7e7 !important; }
        </style>
        <?php
    }
}
if (!function_exists('san_render_rooms')) {
    function san_render_rooms() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'san_rooms';
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($action == 'edit' || $action == 'add') {
            $item = array('title' => '', 'room_class' => '', 'cost' => '0.00', 'total_count' => 1);
            if ($id) {
                $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
            }
            ?>
            <h3><?php echo $id ? __('Редактировать номер', 'sanatorium-management') : __('Добавить номер', 'sanatorium-management'); ?></h3>
            <form method="post" action="?page=sanatorium-management&tab=rooms&action=save">
                <?php wp_nonce_field('san_room_action', 'san_room_nonce'); ?>
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="title"><?php _e('Название/Тип', 'sanatorium-management'); ?></label></th>
                        <td><input name="title" type="text" id="title" value="<?php echo esc_attr($item['title']); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="room_class"><?php _e('Класс', 'sanatorium-management'); ?></label></th>
                        <td><input name="room_class" type="text" id="room_class" value="<?php echo esc_attr($item['room_class']); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cost"><?php _e('Стоимость', 'sanatorium-management'); ?></label></th>
                        <td><input name="cost" type="number" step="0.01" id="cost" value="<?php echo esc_attr($item['cost']); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="total_count"><?php _e('Количество номеров', 'sanatorium-management'); ?></label></th>
                        <td><input name="total_count" type="number" id="total_count" value="<?php echo esc_attr($item['total_count']); ?>" class="regular-text" required></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Сохранить', 'sanatorium-management'); ?>">
                    <a href="?page=sanatorium-management&tab=rooms" class="button"><?php _e('Отмена', 'sanatorium-management'); ?></a>
                </p>
            </form>
            <?php
        } else {
            $items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");
            ?>
            <div class="san-header-actions">
                <h3><?php _e('Номера', 'sanatorium-management'); ?></h3>
                <a href="?page=sanatorium-management&tab=rooms&action=add" class="button button-primary"><?php _e('Добавить номер', 'sanatorium-management'); ?></a>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'sanatorium-management'); ?></th>
                        <th><?php _e('Название', 'sanatorium-management'); ?></th>
                        <th><?php _e('Класс', 'sanatorium-management'); ?></th>
                        <th><?php _e('Стоимость', 'sanatorium-management'); ?></th>
                        <th><?php _e('Кол-во', 'sanatorium-management'); ?></th>
                        <th><?php _e('Действия', 'sanatorium-management'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($items) : foreach ($items as $item) : ?>
                        <tr>
                            <td><?php echo $item->id; ?></td>
                            <td><?php echo esc_html($item->title); ?></td>
                            <td><?php echo esc_html($item->room_class); ?></td>
                            <td><?php echo number_format($item->cost, 2); ?></td>
                            <td><?php echo $item->total_count; ?></td>
                            <td>
                                <a href="?page=sanatorium-management&tab=rooms&action=edit&id=<?php echo $item->id; ?>"><?php _e('Редактировать', 'sanatorium-management'); ?></a> |
                                <a href="?page=sanatorium-management&tab=rooms&action=delete&id=<?php echo $item->id; ?>" onclick="return confirm('<?php _e('Вы уверены?', 'sanatorium-management'); ?>')"><?php _e('Удалить', 'sanatorium-management'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="6"><?php _e('Номера не найдены', 'sanatorium-management'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php
        }
    }
}
if (!function_exists('san_render_procedures')) {
    function san_render_procedures() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'san_procedures';
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($action == 'edit' || $action == 'add') {
            $item = array('title' => '', 'cost' => '0.00');
            if ($id) {
                $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
            }
            ?>
            <h3><?php echo $id ? __('Редактировать процедуру', 'sanatorium-management') : __('Добавить процедуру', 'sanatorium-management'); ?></h3>
            <form method="post" action="?page=sanatorium-management&tab=procedures&action=save">
                <?php wp_nonce_field('san_procedure_action', 'san_procedure_nonce'); ?>
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="title"><?php _e('Название', 'sanatorium-management'); ?></label></th>
                        <td><input name="title" type="text" id="title" value="<?php echo esc_attr($item['title']); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cost"><?php _e('Стоимость', 'sanatorium-management'); ?></label></th>
                        <td><input name="cost" type="number" step="0.01" id="cost" value="<?php echo esc_attr($item['cost']); ?>" class="regular-text" required></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Сохранить', 'sanatorium-management'); ?>">
                    <a href="?page=sanatorium-management&tab=procedures" class="button"><?php _e('Отмена', 'sanatorium-management'); ?></a>
                </p>
            </form>
            <?php
        } else {
            $items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");
            ?>
            <div class="san-header-actions">
                <h3><?php _e('Процедуры', 'sanatorium-management'); ?></h3>
                <a href="?page=sanatorium-management&tab=procedures&action=add" class="button button-primary"><?php _e('Добавить процедуру', 'sanatorium-management'); ?></a>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'sanatorium-management'); ?></th>
                        <th><?php _e('Название', 'sanatorium-management'); ?></th>
                        <th><?php _e('Стоимость', 'sanatorium-management'); ?></th>
                        <th><?php _e('Действия', 'sanatorium-management'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($items) : foreach ($items as $item) : ?>
                        <tr>
                            <td><?php echo $item->id; ?></td>
                            <td><?php echo esc_html($item->title); ?></td>
                            <td><?php echo number_format($item->cost, 2); ?></td>
                            <td>
                                <a href="?page=sanatorium-management&tab=procedures&action=edit&id=<?php echo $item->id; ?>"><?php _e('Редактировать', 'sanatorium-management'); ?></a> |
                                <a href="?page=sanatorium-management&tab=procedures&action=delete&id=<?php echo $item->id; ?>" onclick="return confirm('<?php _e('Вы уверены?', 'sanatorium-management'); ?>')"><?php _e('Удалить', 'sanatorium-management'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="4"><?php _e('Процедуры не найдены', 'sanatorium-management'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php
        }
    }
}
if (!function_exists('san_render_services')) {
    function san_render_services() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'san_services';
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($action == 'edit' || $action == 'add') {
            $item = array('title' => '', 'cost' => '0.00');
            if ($id) {
                $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
            }
            ?>
            <h3><?php echo $id ? __('Редактировать услугу', 'sanatorium-management') : __('Добавить услугу', 'sanatorium-management'); ?></h3>
            <form method="post" action="?page=sanatorium-management&tab=services&action=save">
                <?php wp_nonce_field('san_service_action', 'san_service_nonce'); ?>
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="title"><?php _e('Название', 'sanatorium-management'); ?></label></th>
                        <td><input name="title" type="text" id="title" value="<?php echo esc_attr($item['title']); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cost"><?php _e('Стоимость', 'sanatorium-management'); ?></label></th>
                        <td><input name="cost" type="number" step="0.01" id="cost" value="<?php echo esc_attr($item['cost']); ?>" class="regular-text" required></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Сохранить', 'sanatorium-management'); ?>">
                    <a href="?page=sanatorium-management&tab=services" class="button"><?php _e('Отмена', 'sanatorium-management'); ?></a>
                </p>
            </form>
            <?php
        } else {
            $items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");
            ?>
            <div class="san-header-actions">
                <h3><?php _e('Дополнительные услуги', 'sanatorium-management'); ?></h3>
                <a href="?page=sanatorium-management&tab=services&action=add" class="button button-primary"><?php _e('Добавить услугу', 'sanatorium-management'); ?></a>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'sanatorium-management'); ?></th>
                        <th><?php _e('Название', 'sanatorium-management'); ?></th>
                        <th><?php _e('Стоимость', 'sanatorium-management'); ?></th>
                        <th><?php _e('Действия', 'sanatorium-management'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($items) : foreach ($items as $item) : ?>
                        <tr>
                            <td><?php echo $item->id; ?></td>
                            <td><?php echo esc_html($item->title); ?></td>
                            <td><?php echo number_format($item->cost, 2); ?></td>
                            <td>
                                <a href="?page=sanatorium-management&tab=services&action=edit&id=<?php echo $item->id; ?>"><?php _e('Редактировать', 'sanatorium-management'); ?></a> |
                                <a href="?page=sanatorium-management&tab=services&action=delete&id=<?php echo $item->id; ?>" onclick="return confirm('<?php _e('Вы уверены?', 'sanatorium-management'); ?>')"><?php _e('Удалить', 'sanatorium-management'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="4"><?php _e('Услуги не найдены', 'sanatorium-management'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php
        }
    }
}
if (!function_exists('san_render_packages')) {
    function san_render_packages() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'san_packages';
        $rooms_table = $wpdb->prefix . 'san_rooms';
        $proc_table = $wpdb->prefix . 'san_procedures';

        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($action == 'edit' || $action == 'add') {
            $item = array('title' => '', 'room_id' => 0, 'procedure_ids' => '', 'cost' => '0.00');
            if ($id) {
                $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
            }
            $selected_procs = !empty($item['procedure_ids']) ? explode(',', $item['procedure_ids']) : array();

            $rooms = $wpdb->get_results("SELECT id, title FROM $rooms_table");
            $procedures = $wpdb->get_results("SELECT id, title FROM $proc_table");
            ?>
            <h3><?php echo $id ? __('Редактировать путевку', 'sanatorium-management') : __('Добавить путевку', 'sanatorium-management'); ?></h3>
            <form method="post" action="?page=sanatorium-management&tab=packages&action=save">
                <?php wp_nonce_field('san_package_action', 'san_package_nonce'); ?>
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="title"><?php _e('Название путевки', 'sanatorium-management'); ?></label></th>
                        <td><input name="title" type="text" id="title" value="<?php echo esc_attr($item['title']); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="room_id"><?php _e('Тип номера', 'sanatorium-management'); ?></label></th>
                        <td>
                            <select name="room_id" id="room_id" required>
                                <option value=""><?php _e('Выберите номер', 'sanatorium-management'); ?></option>
                                <?php foreach ($rooms as $room) : ?>
                                    <option value="<?php echo $room->id; ?>" <?php selected($item['room_id'], $room->id); ?>><?php echo esc_html($room->title); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Включенные процедуры', 'sanatorium-management'); ?></th>
                        <td>
                            <div class="san-checkbox-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background: #fff;">
                                <?php foreach ($procedures as $proc) : ?>
                                    <label style="display: block;">
                                        <input type="checkbox" name="procedure_ids[]" value="<?php echo $proc->id; ?>" <?php echo in_array($proc->id, $selected_procs) ? 'checked' : ''; ?>>
                                        <?php echo esc_html($proc->title); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cost"><?php _e('Итоговая стоимость', 'sanatorium-management'); ?></label></th>
                        <td><input name="cost" type="number" step="0.01" id="cost" value="<?php echo esc_attr($item['cost']); ?>" class="regular-text" required></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Сохранить', 'sanatorium-management'); ?>">
                    <a href="?page=sanatorium-management&tab=packages" class="button"><?php _e('Отмена', 'sanatorium-management'); ?></a>
                </p>
            </form>
            <?php
        } else {
            $items = $wpdb->get_results("SELECT p.*, r.title as room_title FROM $table_name p LEFT JOIN $rooms_table r ON p.room_id = r.id ORDER BY p.id DESC");
            ?>
            <div class="san-header-actions">
                <h3><?php _e('Путевки', 'sanatorium-management'); ?></h3>
                <a href="?page=sanatorium-management&tab=packages&action=add" class="button button-primary"><?php _e('Добавить путевку', 'sanatorium-management'); ?></a>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'sanatorium-management'); ?></th>
                        <th><?php _e('Название', 'sanatorium-management'); ?></th>
                        <th><?php _e('Номер', 'sanatorium-management'); ?></th>
                        <th><?php _e('Стоимость', 'sanatorium-management'); ?></th>
                        <th><?php _e('Действия', 'sanatorium-management'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($items) : foreach ($items as $item) : ?>
                        <tr>
                            <td><?php echo $item->id; ?></td>
                            <td><?php echo esc_html($item->title); ?></td>
                            <td><?php echo esc_html($item->room_title); ?></td>
                            <td><?php echo number_format($item->cost, 2); ?></td>
                            <td>
                                <a href="?page=sanatorium-management&tab=packages&action=edit&id=<?php echo $item->id; ?>"><?php _e('Редактировать', 'sanatorium-management'); ?></a> |
                                <a href="?page=sanatorium-management&tab=packages&action=delete&id=<?php echo $item->id; ?>" onclick="return confirm('<?php _e('Вы уверены?', 'sanatorium-management'); ?>')"><?php _e('Удалить', 'sanatorium-management'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="5"><?php _e('Путевки не найдены', 'sanatorium-management'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php
        }
    }
}
if (!function_exists('san_render_settings')) {
    function san_render_settings() {
        if (isset($_POST['san_save_settings']) && check_admin_referer('san_settings_action', 'san_settings_nonce')) {
            update_option('san_visitor_info', wp_kses_post($_POST['san_visitor_info']));
            update_option('san_admin_email', sanitize_email($_POST['san_admin_email']));
            echo '<div class="updated"><p>' . __('Настройки сохранены', 'sanatorium-management') . '</p></div>';
        }

        $visitor_info = get_option('san_visitor_info', '');
        $admin_email = get_option('san_admin_email', get_option('admin_email'));
        ?>
        <h3><?php _e('Настройки', 'sanatorium-management'); ?></h3>
        <form method="post" action="">
            <?php wp_nonce_field('san_settings_action', 'san_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="san_admin_email"><?php _e('Email администратора для уведомлений', 'sanatorium-management'); ?></label></th>
                    <td><input name="san_admin_email" type="email" id="san_admin_email" value="<?php echo esc_attr($admin_email); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="san_visitor_info"><?php _e('Информация для посетителей (на форме бронирования)', 'sanatorium-management'); ?></label></th>
                    <td><?php wp_editor($visitor_info, 'san_visitor_info', array('textarea_rows' => 10)); ?></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="san_save_settings" id="submit" class="button button-primary" value="<?php _e('Сохранить изменения', 'sanatorium-management'); ?>">
            </p>
        </form>
        <?php
    }
}
