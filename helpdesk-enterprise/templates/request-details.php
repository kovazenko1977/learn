<div class="hd-request-details-view">
    <div class="hd-request-grid">
        <div class="hd-main-col">
            <section class="hd-detail-section">
                <h3 style="margin-top: 0; color: var(--hd-primary);"><?php echo esc_html($request->title); ?> <span style="color: var(--hd-secondary); font-weight: 400;">#<?php echo $request->id; ?></span></h3>
                <div class="hd-description" style="background: #f8fafc; padding: 16px; border-radius: 8px; line-height: 1.6;">
                    <?php echo wpautop(esc_html($request->description)); ?>
                </div>
            </section>

            <?php if ($photos): ?>
                <section class="hd-detail-section">
                    <h4 class="hd-detail-title"><?php _e('Прикрепленные фото', 'helpdesk-enterprise'); ?></h4>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <?php foreach ($photos as $photo): ?>
                            <div style="position: relative; border: 1px solid var(--hd-border); padding: 4px; border-radius: 4px;">
                                <a href="<?php echo esc_url($photo->file_url); ?>" target="_blank">
                                    <img src="<?php echo esc_url($photo->file_url); ?>" style="width: 120px; height: 120px; object-fit: cover; border-radius: 2px;">
                                </a>
                                <?php if (HD_Auth::current_user_can('hd_delete_data')): ?>
                                    <button class="hd-delete-photo" data-id="<?php echo $photo->id; ?>" data-request-id="<?php echo $request->id; ?>" style="position: absolute; top: -8px; right: -8px; background: var(--hd-danger); color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px;">&times;</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="hd-detail-section">
                <form id="hd-add-photo-form" style="display: flex; gap: 10px; align-items: center; background: #fff; border: 1px dashed var(--hd-border); padding: 12px; border-radius: 8px;">
                    <label style="font-size: 13px; font-weight: 600;"><?php _e('Добавить фото:', 'helpdesk-enterprise'); ?></label>
                    <input type="file" name="photo" accept="image/*" required class="hd-input" style="width: auto;">
                    <input type="hidden" name="request_id" value="<?php echo $request->id; ?>">
                    <button type="submit" class="hd-btn hd-btn-primary" style="padding: 6px 12px;"><?php _e('Загрузить', 'helpdesk-enterprise'); ?></button>
                </form>
            </section>

            <section class="hd-detail-section">
                <h4 class="hd-detail-title"><?php _e('Комментарии', 'helpdesk-enterprise'); ?></h4>
                <div id="hd-comments-list" style="margin-bottom: 16px; max-height: 300px; overflow-y: auto; padding-right: 8px;">
                    <?php if (!$comments): ?>
                        <p style="color: var(--hd-secondary); font-style: italic;"><?php _e('Пока нет комментариев...', 'helpdesk-enterprise'); ?></p>
                    <?php endif; ?>
                    <?php foreach ($comments as $comment): ?>
                        <div style="margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;">
                            <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                                <span style="font-weight: 700;"><?php $c_user = HD_Auth::get_user_by_id($comment->user_id); echo $c_user ? $c_user->display_name : 'Unknown'; ?></span>
                                <span style="color: var(--hd-secondary);"><?php echo date('d.m.Y H:i', strtotime($comment->created_at)); ?></span>
                            </div>
                            <div style="font-size: 14px;"><?php echo nl2br(esc_html($comment->content)); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="hd-form-group">
                    <textarea id="hd-comment-content" class="hd-textarea" rows="3" placeholder="<?php _e('Напишите сообщение...', 'helpdesk-enterprise'); ?>"></textarea>
                    <button id="hd-submit-comment" data-id="<?php echo $request->id; ?>" class="hd-btn hd-btn-primary" style="margin-top: 8px; width: 100%;"><?php _e('Отправить сообщение', 'helpdesk-enterprise'); ?></button>
                </div>
            </section>
        </div>

        <div class="hd-side-col">
            <section class="hd-detail-section" style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid var(--hd-border);">
                <h4 style="margin-top: 0; font-size: 14px; text-transform: uppercase; color: var(--hd-secondary);"><?php _e('Информация', 'helpdesk-enterprise'); ?></h4>
                <div style="font-size: 14px; display: grid; gap: 8px;">
                    <div><strong><?php _e('Статус:', 'helpdesk-enterprise'); ?></strong> <span class="hd-badge badge-<?php echo $request->status; ?>"><?php echo esc_html($request->status); ?></span></div>
                    <div><strong><?php _e('Ответственный:', 'helpdesk-enterprise'); ?></strong><br><?php $resp = HD_Auth::get_user_by_id($request->responsible_id); echo $resp ? $resp->display_name : 'Unknown'; ?></div>
                    <div><strong><?php _e('Исполнитель:', 'helpdesk-enterprise'); ?></strong><br><?php $exec = $request->executor_id ? HD_Auth::get_user_by_id($request->executor_id) : null; echo $exec ? $exec->display_name : '<span style="color: var(--hd-danger)">Не назначен</span>'; ?></div>
                    <div><strong><?php _e('Отдел:', 'helpdesk-enterprise'); ?></strong><br><?php echo esc_html($request->dept_name); ?></div>
                    <div><strong><?php _e('Создана:', 'helpdesk-enterprise'); ?></strong><br><?php echo date('d.m.Y H:i', strtotime($request->created_at)); ?></div>
                    <div><strong><?php _e('Дедлайн:', 'helpdesk-enterprise'); ?></strong><br>
                        <?php
                            $deadline_ts = strtotime($request->deadline);
                            $is_overdue = ($request->status !== 'completed' && $deadline_ts < current_time('timestamp'));
                        ?>
                        <span style="<?php echo $is_overdue ? 'color: var(--hd-danger); font-weight: 700;' : ''; ?>">
                            <?php echo date('d.m.Y H:i', $deadline_ts); ?>
                        </span>
                    </div>
                    <?php if ($request->completed_at): ?>
                        <div style="color: var(--hd-success);"><strong><?php _e('Завершена:', 'helpdesk-enterprise'); ?></strong><br><?php echo date('d.m.Y H:i', strtotime($request->completed_at)); ?></div>
                    <?php endif; ?>
                </div>
            </section>

            <?php if (HD_Auth::current_user_can('hd_update_status') || HD_Auth::current_user_can('hd_manage_dept') || HD_Auth::current_user_can('hd_manage_all')): ?>
                <section class="hd-detail-section" style="margin-top: 24px;">
                    <h4 class="hd-detail-title"><?php _e('Управление', 'helpdesk-enterprise'); ?></h4>
                    <div class="hd-form-group">
                        <label style="font-size: 12px;"><?php _e('Сменить статус', 'helpdesk-enterprise'); ?></label>
                        <select id="hd-status-change" data-id="<?php echo $request->id; ?>" class="hd-input">
                            <option value="new" <?php selected($request->status, 'new'); ?>>Новая</option>
                            <option value="in_progress" <?php selected($request->status, 'in_progress'); ?>>В работе</option>
                            <option value="pending" <?php selected($request->status, 'pending'); ?>>Ожидание</option>
                            <option value="completed" <?php selected($request->status, 'completed'); ?>>Выполнена</option>
                            <option value="rejected" <?php selected($request->status, 'rejected'); ?>>Отклонена</option>
                        </select>
                        <button id="hd-update-status-btn" class="hd-btn hd-btn-primary" style="margin-top: 4px; width: 100%;"><?php _e('Применить', 'helpdesk-enterprise'); ?></button>
                    </div>

                    <?php if (HD_Auth::current_user_can('hd_manage_dept') || HD_Auth::current_user_can('hd_manage_all')): ?>
                        <div class="hd-form-group">
                            <label style="font-size: 12px;"><?php _e('Исполнитель', 'helpdesk-enterprise'); ?></label>
                            <select id="hd-executor-change" data-id="<?php echo $request->id; ?>" class="hd-input">
                                <option value="0"><?php _e('Выберите...', 'helpdesk-enterprise'); ?></option>
                                <?php
                                global $wpdb;
                                $executors = $wpdb->get_results("SELECT id, display_name FROM {$wpdb->prefix}hd_users WHERE role IN ('hd_executor', 'hd_department_head', 'hd_administrator')");
                                foreach ($executors as $exec): ?>
                                    <option value="<?php echo $exec->id; ?>" <?php selected($request->executor_id, $exec->id); ?>><?php echo esc_html($exec->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button id="hd-assign-executor-btn" class="hd-btn hd-btn-primary" style="margin-top: 4px; width: 100%;"><?php _e('Назначить', 'helpdesk-enterprise'); ?></button>
                        </div>

                        <div class="hd-form-group">
                            <label style="font-size: 12px;"><?php _e('Продлить срок', 'helpdesk-enterprise'); ?></label>
                            <input type="datetime-local" id="hd-deadline-change" class="hd-input" value="<?php echo date('Y-m-d\TH:i', strtotime($request->deadline)); ?>">
                            <button id="hd-update-deadline-btn" data-id="<?php echo $request->id; ?>" class="hd-btn hd-btn-primary" style="margin-top: 4px; width: 100%;"><?php _e('Обновить дедлайн', 'helpdesk-enterprise'); ?></button>
                        </div>

                        <?php if (HD_Auth::current_user_can('hd_delete_data')): ?>
                            <button id="hd-delete-request-btn" data-id="<?php echo $request->id; ?>" class="hd-btn" style="background: transparent; color: var(--hd-danger); border: 1px solid var(--hd-danger); width: 100%; margin-top: 16px;"><?php _e('Удалить заявку', 'helpdesk-enterprise'); ?></button>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="hd-detail-section">
                <h4 class="hd-detail-title"><?php _e('История', 'helpdesk-enterprise'); ?></h4>
                <ul class="hd-history-list">
                    <?php foreach ($history as $event): ?>
                        <li class="hd-history-item">
                            <div class="hd-history-time"><?php echo date('d.m.Y H:i', strtotime($event->created_at)); ?></div>
                            <div style="font-weight: 600;">
                                <?php
                                $types = array(
                                    'status_changed' => __('Статус изменен', 'helpdesk-enterprise'),
                                    'executor_changed' => __('Исполнитель изменен', 'helpdesk-enterprise'),
                                    'deadline_changed' => __('Срок изменен', 'helpdesk-enterprise'),
                                    'comment_added' => __('Добавлен комментарий', 'helpdesk-enterprise'),
                                    'photo_added' => __('Добавлено фото', 'helpdesk-enterprise'),
                                    'comment_deleted' => __('Удален комментарий', 'helpdesk-enterprise'),
                                    'photo_deleted' => __('Удалено фото', 'helpdesk-enterprise'),
                                    'request_created' => __('Создана заявка', 'helpdesk-enterprise'),
                                );
                                echo isset($types[$event->event_type]) ? $types[$event->event_type] : $event->event_type;
                                ?>
                            </div>
                            <div style="font-size: 11px; color: var(--hd-secondary);">
                                <?php if ($event->user_id): $e_user = HD_Auth::get_user_by_id($event->user_id); echo $e_user ? $e_user->display_name : 'Deleted User'; else: echo 'System'; endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </div>
    </div>
</div>
