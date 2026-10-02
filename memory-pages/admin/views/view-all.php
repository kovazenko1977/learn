<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'memorials';
$items = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC", ARRAY_A);
?>
<div class="wrap mp-admin-wrap">
    <h1 class="wp-heading-inline">Страницы памяти</h1>
    <a href="<?php echo admin_url('admin.php?page=memory-pages-add'); ?>" class="page-title-action">Добавить страницу</a>
    <hr class="wp-header-end">

    <div class="mp-card">
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th width="80">ID</th>
                    <th width="120">Цифровой код</th>
                    <th>ФИО</th>
                    <th width="120">Дата рождения</th>
                    <th width="120">Дата смерти</th>
                    <th width="120">Статус</th>
                    <th width="180">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="7">Страниц памяти пока нет. Нажмите «Добавить страницу», чтобы создать первую.</td></tr>
                <?php else: foreach ($items as $item):
                    $public_url = home_url('/memory/' . $item['code'] . '/');
                    $status_names = array(
                        'draft' => 'Черновик',
                        'pending' => 'На проверке',
                        'published' => 'Опубликовано',
                        'disabled' => 'Отключено',
                        'archive' => 'Архив'
                    );
                ?>
                    <tr>
                        <td><?php echo esc_html($item['id']); ?></td>
                        <td><strong><?php echo esc_html($item['code']); ?></strong></td>
                        <td>
                            <strong><a href="<?php echo admin_url('admin.php?page=memory-pages-add&id=' . $item['id']); ?>"><?php echo esc_html($item['full_name']); ?></a></strong>
                        </td>
                        <td><?php echo esc_html($item['birth_date']); ?></td>
                        <td><?php echo esc_html($item['death_date']); ?></td>
                        <td><span class="mp-badge mp-badge-<?php echo esc_attr($item['status']); ?>"><?php echo esc_html($status_names[$item['status']] ?? $item['status']); ?></span></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=memory-pages-add&id=' . $item['id']); ?>" class="button button-small">Изменить</a>
                            <a href="<?php echo esc_url(add_query_arg('preview', '1', $public_url)); ?>" target="_blank" class="button button-small">👁 Предпросмотр</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
