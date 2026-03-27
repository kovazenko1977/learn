<div class="hd-request-detail">
    <h3><?php echo esc_html($request->title); ?> (#<?php echo $request->id; ?>)</h3>
    <div class="hd-meta">
        <p><strong><?php _e('Статус:', 'helpdesk-enterprise'); ?></strong> <?php echo esc_html($request->status); ?></p>
        <p><strong><?php _e('Категория:', 'helpdesk-enterprise'); ?></strong> <?php echo esc_html($request->cat_name); ?></p>
        <p><strong><?php _e('Отдел:', 'helpdesk-enterprise'); ?></strong> <?php echo esc_html($request->dept_name); ?></p>
        <p><strong><?php _e('Срок (SLA):', 'helpdesk-enterprise'); ?></strong> <?php echo esc_html($request->deadline); ?></p>
        <?php
        global $wpdb;
        $priority = $wpdb->get_var($wpdb->prepare("SELECT priority FROM {$wpdb->prefix}hd_categories WHERE id = %d", $request->category_id));
        if ($priority): ?>
            <p><strong><?php _e('Приоритет:', 'helpdesk-enterprise'); ?></strong> <?php echo esc_html($priority); ?></p>
        <?php endif; ?>
    </div>

    <div class="hd-description">
        <h4><?php _e('Описание', 'helpdesk-enterprise'); ?></h4>
        <p><?php echo wpautop(esc_html($request->description)); ?></p>
    </div>

    <?php if ($photos): ?>
        <div class="hd-photos">
            <h4><?php _e('Фотографии', 'helpdesk-enterprise'); ?></h4>
            <div class="hd-photo-gallery">
                <?php foreach ($photos as $photo): ?>
                    <div class="hd-photo-item">
                        <a href="<?php echo esc_url($photo->file_url); ?>" target="_blank">
                            <img src="<?php echo esc_url($photo->file_url); ?>" style="max-width: 150px; height: auto; margin: 5px;">
                        </a>
                        <?php if (current_user_can('hd_delete_data')): ?>
                            <button class="hd-delete-photo" data-id="<?php echo $photo->id; ?>" data-request-id="<?php echo $request->id; ?>">×</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (current_user_can('hd_update_status') || current_user_can('hd_manage_dept_requests') || current_user_can('hd_manage_all')): ?>
        <div class="hd-actions" style="border-top: 1px solid #ddd; padding-top: 15px;">
            <h4><?php _e('Управление заявкой', 'helpdesk-enterprise'); ?></h4>
            <div style="margin-bottom: 10px;">
                <label><?php _e('Сменить статус:', 'helpdesk-enterprise'); ?></label><br>
                <select id="hd-status-change" data-id="<?php echo $request->id; ?>">
                    <option value="new" <?php selected($request->status, 'new'); ?>>Новая</option>
                    <option value="in_progress" <?php selected($request->status, 'in_progress'); ?>>В работе</option>
                    <option value="pending" <?php selected($request->status, 'pending'); ?>>Ожидание</option>
                    <option value="completed" <?php selected($request->status, 'completed'); ?>>Выполнена</option>
                    <option value="rejected" <?php selected($request->status, 'rejected'); ?>>Отклонена</option>
                </select>
                <button id="hd-update-status-btn" class="button"><?php _e('Обновить', 'helpdesk-enterprise'); ?></button>
            </div>

            <?php if (current_user_can('hd_manage_dept_requests') || current_user_can('hd_manage_all')): ?>
                <div style="margin-bottom: 10px;">
                    <label><?php _e('Назначить исполнителя:', 'helpdesk-enterprise'); ?></label><br>
                    <select id="hd-executor-change" data-id="<?php echo $request->id; ?>">
                        <option value="0"><?php _e('Не назначен', 'helpdesk-enterprise'); ?></option>
                        <?php
                        $executors = get_users(array('role__in' => array('hd_executor', 'hd_department_head', 'hd_administrator', 'administrator')));
                        foreach ($executors as $exec): ?>
                            <option value="<?php echo $exec->ID; ?>" <?php selected($request->executor_id, $exec->ID); ?>><?php echo esc_html($exec->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button id="hd-assign-executor-btn" class="button"><?php _e('Назначить', 'helpdesk-enterprise'); ?></button>
                </div>

                <div style="margin-bottom: 10px;">
                    <label><?php _e('Изменить срок (SLA):', 'helpdesk-enterprise'); ?></label><br>
                    <input type="datetime-local" id="hd-deadline-change" value="<?php echo date('Y-m-d\TH:i', strtotime($request->deadline)); ?>">
                    <button id="hd-update-deadline-btn" data-id="<?php echo $request->id; ?>" class="button"><?php _e('Изменить срок', 'helpdesk-enterprise'); ?></button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="hd-comments-section">
        <h4><?php _e('Комментарии', 'helpdesk-enterprise'); ?></h4>
        <div id="hd-comments-list">
            <?php foreach ($comments as $comment): ?>
                <div class="hd-comment">
                    <strong><?php echo get_userdata($comment->user_id)->display_name; ?>:</strong>
                    <span><?php echo esc_html($comment->content); ?></span>
                    <small>(<?php echo $comment->created_at; ?>)</small>
                </div>
            <?php endforeach; ?>
        </div>
        <textarea id="hd-comment-content" placeholder="<?php _e('Добавить комментарий...', 'helpdesk-enterprise'); ?>"></textarea>
        <button id="hd-submit-comment" data-id="<?php echo $request->id; ?>" class="button"><?php _e('Добавить комментарий', 'helpdesk-enterprise'); ?></button>
    </div>

    <div class="hd-history-section">
        <h4><?php _e('История изменений', 'helpdesk-enterprise'); ?></h4>
        <ul class="hd-history-list">
            <?php foreach ($history as $event): ?>
                <li>
                    <strong><?php
                        $types = array(
                            'status_changed' => __('Смена статуса', 'helpdesk-enterprise'),
                            'executor_changed' => __('Смена исполнителя', 'helpdesk-enterprise'),
                            'deadline_changed' => __('Изменение срока', 'helpdesk-enterprise'),
                            'comment_added' => __('Добавлен комментарий', 'helpdesk-enterprise'),
                            'photo_added' => __('Добавлено фото', 'helpdesk-enterprise'),
                            'request_created' => __('Заявка создана', 'helpdesk-enterprise'),
                        );
                        echo isset($types[$event->event_type]) ? $types[$event->event_type] : esc_html($event->event_type);
                    ?></strong> пользователем
                    <?php echo $event->user_id ? get_userdata($event->user_id)->display_name : 'Система'; ?>
                    в <?php echo $event->created_at; ?>
                    <?php if ($event->old_value || $event->new_value): ?>
                        <br><small>
                            <?php
                                $old = $event->old_value;
                                $new = $event->new_value;
                                if ($event->event_type === 'request_created') {
                                    $new_data = json_decode($new, true);
                                    if ($new_data) $new = $new_data['title'];
                                }
                            ?>
                            <?php if ($old): ?><i><?php _e('Было:', 'helpdesk-enterprise'); ?></i> <?php echo esc_html($old); ?><?php endif; ?>
                            <?php if ($new): ?> <i><?php _e('Стало:', 'helpdesk-enterprise'); ?></i> <?php echo esc_html($new); ?><?php endif; ?>
                        </small>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
