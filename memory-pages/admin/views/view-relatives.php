<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$relatives = $wpdb->get_results("SELECT r.*, m.full_name as memorial_name, m.code FROM {$wpdb->prefix}memorial_relatives r LEFT JOIN {$wpdb->prefix}memorials m ON r.memorial_id = m.id ORDER BY r.id DESC", ARRAY_A);
?>
<div class="wrap mp-admin-wrap">
    <h1>Родственники и контактные лица</h1>
    <div class="mp-card">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ФИО</th>
                    <th>Страница памяти</th>
                    <th>Степень родства</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Telegram</th>
                    <th>Виден на сайте</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($relatives as $rel): ?>
                    <tr>
                        <td><strong><?php echo esc_html($rel['full_name']); ?></strong></td>
                        <td><?php echo esc_html($rel['memorial_name'] . ' (' . $rel['code'] . ')'); ?></td>
                        <td><?php echo esc_html($rel['kinship_degree']); ?></td>
                        <td><?php echo esc_html($rel['phone']); ?></td>
                        <td><?php echo esc_html($rel['email']); ?></td>
                        <td><?php echo esc_html($rel['telegram']); ?></td>
                        <td><?php echo $rel['show_contact_to_visitor'] ? 'Да' : 'Нет'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
