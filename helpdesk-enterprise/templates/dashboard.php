<div class="hd-dashboard-wrapper">
    <header class="hd-header">
        <h2 class="hd-title"><?php _e('Панель управления заявками', 'helpdesk-enterprise'); ?></h2>
        <div class="hd-user-settings" style="display: flex; gap: 20px; align-items: center;">
            <div style="text-align: right; line-height: 1.2;">
                <div style="font-weight: 700; font-size: 14px;"><?php echo esc_html(HD_Auth::get_user()->display_name); ?></div>
                <div style="font-size: 12px; color: var(--hd-secondary);"><?php echo esc_html(HD_Auth::get_user()->role); ?></div>
            </div>
            <form id="hd-user-settings-form" class="hd-controls" style="margin-bottom: 0;">
                <label style="font-size: 11px; color: var(--hd-secondary);"><?php _e('API Token:', 'helpdesk-enterprise'); ?> <code style="background: #eee; padding: 2px 4px;"><?php echo esc_html(HD_Auth::get_user()->api_token); ?></code></label>
                <label style="font-size: 13px; color: var(--hd-secondary);"><?php _e('TG ID:', 'helpdesk-enterprise'); ?></label>
                <input type="text" name="telegram_chat_id" class="hd-input" value="<?php echo esc_attr(HD_Auth::get_user()->telegram_chat_id); ?>" style="width: 100px;">
                <button type="submit" class="hd-btn hd-btn-primary"><?php _e('OK', 'helpdesk-enterprise'); ?></button>
            </form>
            <button id="hd-logout-btn" class="hd-btn" style="background: var(--hd-danger); color: white; padding: 6px 12px; font-size: 12px;"><?php _e('Выход', 'helpdesk-enterprise'); ?></button>
        </div>
    </header>

    <div class="hd-stats-grid">
        <div class="hd-stat-card">
            <span class="hd-stat-value"><?php echo $stats['total']; ?></span>
            <span class="hd-stat-label"><?php _e('Всего', 'helpdesk-enterprise'); ?></span>
        </div>
        <div class="hd-stat-card">
            <span class="hd-stat-value" style="color: var(--hd-primary);"><?php echo $stats['new']; ?></span>
            <span class="hd-stat-label"><?php _e('Новые', 'helpdesk-enterprise'); ?></span>
        </div>
        <div class="hd-stat-card">
            <span class="hd-stat-value" style="color: var(--hd-warning);"><?php echo $stats['in_progress']; ?></span>
            <span class="hd-stat-label"><?php _e('В работе', 'helpdesk-enterprise'); ?></span>
        </div>
        <div class="hd-stat-card">
            <span class="hd-stat-value" style="color: var(--hd-success);"><?php echo $stats['completed']; ?></span>
            <span class="hd-stat-label"><?php _e('Выполнено', 'helpdesk-enterprise'); ?></span>
        </div>
        <div class="hd-stat-card" style="border-color: var(--hd-danger);">
            <span class="hd-stat-value" style="color: var(--hd-danger);"><?php echo $stats['overdue']; ?></span>
            <span class="hd-stat-label"><?php _e('Просрочено', 'helpdesk-enterprise'); ?></span>
        </div>
    </div>

    <section class="hd-controls">
        <form method="get" style="display: flex; gap: 10px; align-items: center;">
            <input type="hidden" name="page_id" value="<?php echo get_the_ID(); ?>">
            <select name="status_filter" class="hd-filter-select">
                <option value=""><?php _e('Все статусы', 'helpdesk-enterprise'); ?></option>
                <option value="new" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'new'); ?>><?php _e('Новые', 'helpdesk-enterprise'); ?></option>
                <option value="in_progress" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'in_progress'); ?>><?php _e('В работе', 'helpdesk-enterprise'); ?></option>
                <option value="completed" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'completed'); ?>><?php _e('Выполнены', 'helpdesk-enterprise'); ?></option>
            </select>
            <select name="cat_filter" class="hd-filter-select">
                <option value=""><?php _e('Все категории', 'helpdesk-enterprise'); ?></option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat->id; ?>" <?php selected(isset($_GET['cat_filter']) ? $_GET['cat_filter'] : '', $cat->id); ?>><?php echo esc_html($cat->name); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="hd-btn hd-btn-primary"><?php _e('Фильтр', 'helpdesk-enterprise'); ?></button>
            <a href="<?php echo get_permalink(); ?>" class="hd-btn" style="background: var(--hd-border);"><?php _e('Сброс', 'helpdesk-enterprise'); ?></a>
        </form>

        <?php if (HD_Auth::current_user_can('hd_export_dept') || HD_Auth::current_user_can('hd_manage_all')): ?>
            <a href="<?php echo admin_url('admin-ajax.php?action=hd_export_requests&nonce=' . wp_create_nonce('hd_nonce')); ?>" class="hd-btn" style="background: var(--hd-secondary); color: white;"><?php _e('Экспорт CSV', 'helpdesk-enterprise'); ?></a>
        <?php endif; ?>
    </section>

    <?php if (HD_Auth::current_user_can('hd_create_requests')): ?>
        <section class="hd-create-form-container">
            <h3 style="margin-top: 0;"><?php _e('Создать новую заявку', 'helpdesk-enterprise'); ?></h3>
            <form id="hd-create-form">
                <div class="hd-form-group">
                    <label><?php _e('Заголовок', 'helpdesk-enterprise'); ?></label>
                    <input type="text" name="title" class="hd-input" required placeholder="<?php _e('Краткая суть проблемы...', 'helpdesk-enterprise'); ?>">
                </div>
                <div class="hd-form-group">
                    <label><?php _e('Описание', 'helpdesk-enterprise'); ?></label>
                    <textarea name="description" class="hd-textarea" rows="4" required placeholder="<?php _e('Подробное описание...', 'helpdesk-enterprise'); ?>"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="hd-form-group">
                        <label><?php _e('Категория', 'helpdesk-enterprise'); ?></label>
                        <select name="category_id" class="hd-input" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat->id; ?>"><?php echo esc_html($cat->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="hd-form-group">
                        <label><?php _e('Прикрепить фото', 'helpdesk-enterprise'); ?></label>
                        <input type="file" name="photo" class="hd-input" accept="image/*">
                    </div>
                </div>
                <button type="submit" class="hd-btn hd-btn-primary" style="width: 100%; padding: 12px; font-size: 16px;"><?php _e('Отправить заявку', 'helpdesk-enterprise'); ?></button>
            </form>
        </section>
    <?php endif; ?>

    <div class="hd-table-container">
        <table class="hd-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th><?php _e('Заявка', 'helpdesk-enterprise'); ?></th>
                    <th><?php _e('Статус', 'helpdesk-enterprise'); ?></th>
                    <th><?php _e('Отдел', 'helpdesk-enterprise'); ?></th>
                    <th><?php _e('Дедлайн', 'helpdesk-enterprise'); ?></th>
                    <th style="text-align: right;"><?php _e('Действия', 'helpdesk-enterprise'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td>#<?php echo $request->id; ?></td>
                        <td>
                            <div style="font-weight: 600;"><?php echo esc_html($request->title); ?></div>
                            <div style="font-size: 12px; color: var(--hd-secondary);"><?php echo esc_html($request->cat_name); ?></div>
                        </td>
                        <td><span class="hd-badge badge-<?php echo $request->status; ?>">
                            <?php
                                $status_labels = array('new' => 'Новая', 'in_progress' => 'В работе', 'pending' => 'Ожидание', 'completed' => 'Выполнена', 'rejected' => 'Отклонена');
                                echo isset($status_labels[$request->status]) ? $status_labels[$request->status] : $request->status;
                            ?>
                        </span></td>
                        <td><?php echo esc_html($request->dept_name); ?></td>
                        <td>
                            <?php
                                $deadline_ts = strtotime($request->deadline);
                                $is_overdue = ($request->status !== 'completed' && $deadline_ts < current_time('timestamp'));
                            ?>
                            <span style="<?php echo $is_overdue ? 'color: var(--hd-danger); font-weight: 700;' : ''; ?>">
                                <?php echo date('d.m.Y H:i', $deadline_ts); ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <button class="hd-btn hd-view-request" data-id="<?php echo $request->id; ?>" style="background: #f1f5f9; color: var(--hd-primary);"><?php _e('Открыть', 'helpdesk-enterprise'); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="hd-modal">
        <div class="hd-modal-content">
            <div class="hd-modal-header">
                <h3 style="margin: 0;"><?php _e('Детали заявки', 'helpdesk-enterprise'); ?></h3>
                <span class="hd-close">&times;</span>
            </div>
            <div class="hd-modal-body" id="hd-request-details"></div>
        </div>
    </div>
</div>
