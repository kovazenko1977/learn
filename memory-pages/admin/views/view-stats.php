<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$stats = Memory_Pages_Stats::get_overall_stats();
?>
<div class="wrap mp-admin-wrap">
    <h1>Статистика просмотров и активности</h1>
    <div class="mp-card">
        <h3>Сводная статистика событий</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Страница памяти</th>
                    <th>Просмотры</th>
                    <th>QR-переходы</th>
                    <th>Поиск</th>
                    <th>Прямые ссылки</th>
                    <th>Контакты</th>
                    <th>Цветы</th>
                    <th>Свечи</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stats)): ?>
                    <tr><td colspan="8">Статистики пока нет.</td></tr>
                <?php else: foreach ($stats as $row): ?>
                    <tr>
                        <td><strong><?php echo esc_html($row['full_name'] . ' (' . $row['code'] . ')'); ?></strong></td>
                        <td><?php echo esc_html($row['views']); ?></td>
                        <td><?php echo esc_html($row['qr_views']); ?></td>
                        <td><?php echo esc_html($row['search_views']); ?></td>
                        <td><?php echo esc_html($row['direct_views']); ?></td>
                        <td><?php echo esc_html($row['contacts']); ?></td>
                        <td><?php echo esc_html($row['flowers']); ?></td>
                        <td><?php echo esc_html($row['candles']); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
