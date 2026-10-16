<?php
if (!defined('ABSPATH')) {
    exit;
}

// Add WP Admin Menu
add_action('admin_menu', 'mp_add_admin_menu');

function mp_add_admin_menu() {
    add_menu_page(
        'Страницы Памяти',
        '🕯️ Страницы Памяти',
        'manage_options',
        'memory-pages-admin',
        'mp_admin_dashboard_page',
        'dashicons-nametag',
        30
    );

    add_submenu_page(
        'memory-pages-admin',
        'Заявки на модерацию',
        'Заявки на модерацию',
        'manage_options',
        'memory-pages-admin',
        'mp_admin_dashboard_page'
    );

    add_submenu_page(
        'memory-pages-admin',
        'Все мемориалы',
        'Все мемориалы',
        'manage_options',
        'memory-pages-all',
        'mp_admin_all_pages'
    );

    add_submenu_page(
        'memory-pages-admin',
        'Пользователи',
        'Пользователи',
        'manage_options',
        'memory-pages-users',
        'mp_admin_users_page'
    );

    add_submenu_page(
        'memory-pages-admin',
        'Бэкап & Настройки',
        'Бэкап & Настройки',
        'manage_options',
        'memory-pages-backup',
        'mp_admin_backup_page'
    );
}

// Handle Export in admin_init BEFORE headers are sent
add_action('admin_init', function() {
    if (isset($_POST['mp_export_backup']) && check_admin_referer('mp_backup_nonce')) {
        global $wpdb;
        $table_pages = $wpdb->prefix . 'memorial_pages';
        $table_photos = $wpdb->prefix . 'memorial_photos';
        $table_condolences = $wpdb->prefix . 'memorial_condolences';
        $table_users = $wpdb->prefix . 'memorial_users';

        $export_data = array(
            'version' => '2.0.0',
            'exported_at' => date('Y-m-d H:i:s'),
            'pages' => $wpdb->get_results("SELECT * FROM $table_pages", ARRAY_A),
            'photos' => $wpdb->get_results("SELECT * FROM $table_photos", ARRAY_A),
            'condolences' => $wpdb->get_results("SELECT * FROM $table_condolences", ARRAY_A),
            'users' => $wpdb->get_results("SELECT * FROM $table_users", ARRAY_A),
        );

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="memory_pages_backup_' . date('Y_m_d_H_i') . '.json"');
        echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
});

// Page 1: Moderation Dashboard
function mp_admin_dashboard_page() {
    global $wpdb;
    $table_pages = $wpdb->prefix . 'memorial_pages';

    // Handle Moderation Action
    if (isset($_POST['mp_action']) && check_admin_referer('mp_admin_action')) {
        $page_id = intval($_POST['page_id']);
        if ($_POST['mp_action'] === 'approve') {
            $wpdb->update($table_pages, array('status' => 'approved'), array('id' => $page_id));
            echo '<div class="notice notice-success"><p>Страница памяти успешно опубликована!</p></div>';
        } else if ($_POST['mp_action'] === 'reject') {
            $reason = sanitize_text_field($_POST['rejection_reason']);
            $wpdb->update($table_pages, array('status' => 'rejected', 'rejection_reason' => $reason), array('id' => $page_id));
            echo '<div class="notice notice-warning"><p>Заявка отклонена.</p></div>';
        }
    }

    $pending_pages = $wpdb->get_results("SELECT * FROM $table_pages WHERE status = 'pending' ORDER BY id DESC");
    ?>
    <div class="wrap">
        <h1>🕯️ Заявки на модерацию мемориальных страниц</h1>
        <p>Ниже представлены новые страницы, созданные родственниками, ожидающие вашей проверки перед публикацией.</p>

        <?php if (empty($pending_pages)): ?>
            <div class="notice notice-info"><p>Нет новых заявок, ожидающих модерации.</p></div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 60px;">Фото</th>
                        <th>ФИО Усопшего</th>
                        <th>Даты жизни</th>
                        <th>Место захоронения</th>
                        <th>Дата подачи</th>
                        <th style="width: 280px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_pages as $page): ?>
                        <tr>
                            <td>
                                <img src="<?php echo esc_url($page->main_photo ? $page->main_photo : MEMORY_PAGES_URL . 'assets/images/default-avatar.png'); ?>" style="width:50px; height:50px; border-radius:50%; object-fit:cover;">
                            </td>
                            <td><strong><?php echo esc_html($page->full_name); ?></strong></td>
                            <td><?php echo esc_html($page->birth_date); ?> — <?php echo esc_html($page->death_date); ?></td>
                            <td><?php echo esc_html($page->burial_location); ?></td>
                            <td><?php echo esc_html($page->created_at); ?></td>
                            <td>
                                <form method="POST" style="display:inline-block; margin-right:6px;">
                                    <?php wp_nonce_field('mp_admin_action'); ?>
                                    <input type="hidden" name="page_id" value="<?php echo $page->id; ?>">
                                    <input type="hidden" name="mp_action" value="approve">
                                    <button type="submit" class="button button-primary">Опубликовать</button>
                                </form>
                                <form method="POST" style="display:inline-block;">
                                    <?php wp_nonce_field('mp_admin_action'); ?>
                                    <input type="hidden" name="page_id" value="<?php echo $page->id; ?>">
                                    <input type="hidden" name="mp_action" value="reject">
                                    <input type="text" name="rejection_reason" placeholder="Причина отказа" style="width:110px;" required>
                                    <button type="submit" class="button button-secondary">Отклонить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

// Page 2: All Memorial Pages
function mp_admin_all_pages() {
    global $wpdb;
    $table_pages = $wpdb->prefix . 'memorial_pages';
    $pages = $wpdb->get_results("SELECT * FROM $table_pages ORDER BY id DESC");
    ?>
    <div class="wrap">
        <h1>📜 Все мемориальные страницы</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th style="width:60px;">Фото</th>
                    <th>ФИО Усопшего</th>
                    <th>Статус</th>
                    <th>Ссылка</th>
                    <th>QR-код</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $p): ?>
                    <tr>
                        <td><?php echo $p->id; ?></td>
                        <td><img src="<?php echo esc_url($p->main_photo ? $p->main_photo : MEMORY_PAGES_URL . 'assets/images/default-avatar.png'); ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover;"></td>
                        <td><strong><?php echo esc_html($p->full_name); ?></strong></td>
                        <td>
                            <?php if ($p->status === 'approved'): ?>
                                <span style="color:green; font-weight:bold;">Опубликовано</span>
                            <?php elseif ($p->status === 'pending'): ?>
                                <span style="color:orange; font-weight:bold;">На модерации</span>
                            <?php else: ?>
                                <span style="color:red; font-weight:bold;">Отклонено</span>
                            <?php endif; ?>
                        </td>
                        <td><a href="<?php echo esc_url(home_url('/memory/' . $p->slug . '/')); ?>" target="_blank">Открыть страницу</a></td>
                        <td><code>[memorial_qr_code id="<?php echo $p->id; ?>"]</code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Page 3: Users
function mp_admin_users_page() {
    global $wpdb;
    $table_users = $wpdb->prefix . 'memorial_users';
    $users = $wpdb->get_results("SELECT * FROM $table_users ORDER BY id DESC");
    ?>
    <div class="wrap">
        <h1>👥 Пользователи системы (Родственники)</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Телефон (Логин)</th>
                    <th>ФИО</th>
                    <th>Дата регистрации</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo $u->id; ?></td>
                        <td><strong><?php echo esc_html($u->phone); ?></strong></td>
                        <td><?php echo esc_html($u->full_name); ?></td>
                        <td><?php echo esc_html($u->created_at); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Page 4: Backup Export / Import & Settings
function mp_admin_backup_page() {
    ?>
    <div class="wrap">
        <h1>📦 Бэкап, Резервное копирование и Настройки</h1>
        <div class="card" style="max-width:600px; padding:20px; margin-top:20px;">
            <h2>Экспорт всех данных (JSON)</h2>
            <p>Вы можете скачать полный дамп базы данных мемориалов, фотографий, пользователей и соболезнований в формате JSON.</p>
            <form method="POST">
                <?php wp_nonce_field('mp_backup_nonce'); ?>
                <button type="submit" name="mp_export_backup" class="button button-primary">📥 Скачать полный бэкап (.json)</button>
            </form>
        </div>
    </div>
    <?php
}
