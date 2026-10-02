<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$logs = $wpdb->get_results("SELECT l.*, m.full_name, m.code, u.display_name FROM {$wpdb->prefix}memorial_logs l LEFT JOIN {$wpdb->prefix}memorials m ON l.memorial_id = m.id LEFT JOIN {$wpdb->prefix}users u ON l.user_id = u.ID ORDER BY l.id DESC LIMIT 100", ARRAY_A);
?>
<div class="wrap mp-admin-wrap">
    <h1>Журнал действий и история версий</h1>
    <div class="mp-card">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="140">Дата и время</th>
                    <th>Мемориал</th>
                    <th>Пользователь</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html($log['created_at']); ?></td>
                        <td><?php echo esc_html($log['full_name'] . ' (' . $log['code'] . ')'); ?></td>
                        <td><?php echo esc_html($log['display_name'] ?: 'Система'); ?></td>
                        <td><code><?php echo esc_html($log['action']); ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
