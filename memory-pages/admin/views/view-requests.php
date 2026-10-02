<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'memorial_requests';
$requests = $wpdb->get_results("SELECT r.*, m.full_name as memorial_name, m.code FROM $table r LEFT JOIN {$wpdb->prefix}memorials m ON r.memorial_id = m.id ORDER BY r.id DESC", ARRAY_A);
?>
<div class="wrap mp-admin-wrap">
    <h1>Заявки родственников и посетителей</h1>
    <div class="mp-card">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="60">ID</th>
                    <th>Мемориал</th>
                    <th>Заявитель</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Сообщение</th>
                    <th width="100">Статус</th>
                    <th width="120">Дата</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="8">Заявок пока нет.</td></tr>
                <?php else: foreach ($requests as $req): ?>
                    <tr>
                        <td><?php echo esc_html($req['id']); ?></td>
                        <td><?php echo esc_html($req['memorial_name'] ? $req['memorial_name'] . ' (' . $req['code'] . ')' : 'Общая'); ?></td>
                        <td><strong><?php echo esc_html($req['applicant_name']); ?></strong></td>
                        <td><?php echo esc_html($req['phone']); ?></td>
                        <td><?php echo esc_html($req['email']); ?></td>
                        <td><?php echo esc_html($req['message']); ?></td>
                        <td><span class="mp-badge mp-badge-<?php echo esc_attr($req['status']); ?>"><?php echo esc_html($req['status']); ?></span></td>
                        <td><?php echo esc_html($req['created_at']); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
