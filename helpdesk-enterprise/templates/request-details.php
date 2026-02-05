<div class="hd-request-details-view">
    <div class="hd-request-grid">
        <div class="hd-main-col">
            <section class="hd-detail-section">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                    <h2 style="margin: 0; color: var(--win-accent); font-weight: 700; font-size: 24px;">
                        <?php echo esc_html($request->title); ?>
                        <span style="font-weight: 400; color: var(--win-text-sec); font-size: 18px;">#<?php echo $request->id; ?></span>
                    </h2>
                    <span class="hd-badge badge-<?php echo $request->status; ?>" style="padding: 6px 16px; font-size: 14px;">
                        <?php
                            $status_labels = array('new' => 'Новая', 'in_progress' => 'В работе', 'pending' => 'Ожидание', 'completed' => 'Выполнена', 'rejected' => 'Отклонена');
                            echo isset($status_labels[$request->status]) ? $status_labels[$request->status] : $request->status;
                        ?>
                    </span>
                </div>
                <div class="hd-description" style="background: #fdfdfd; border: 1px solid var(--win-border); padding: 24px; border-radius: var(--win-radius-sm); line-height: 1.7; font-size: 15px;">
                    <?php echo wpautop(esc_html($request->description)); ?>
                </div>
            </section>

            <?php if ($photos): ?>
                <section class="hd-detail-section">
                    <h4 class="hd-detail-title" style="display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="image" style="width: 18px; height: 18px;"></i>
                        <?php _e('Галерея вложений', 'helpdesk-enterprise'); ?>
                    </h4>
                    <div style="display: flex; gap: 16px; flex-wrap: wrap; background: #fafafa; padding: 16px; border-radius: var(--win-radius-sm); border: 1px solid var(--win-border);">
                        <?php foreach ($photos as $photo): ?>
                            <div style="position: relative; border: 2px solid #fff; box-shadow: 0 4px 8px rgba(0,0,0,0.05); border-radius: 6px; overflow: hidden;">
                                <a href="<?php echo esc_url($photo->file_url); ?>" target="_blank">
                                    <img src="<?php echo esc_url($photo->file_url); ?>" style="width: 140px; height: 140px; object-fit: cover;">
                                </a>
                                <?php if (HD_Auth::current_user_can('hd_delete_data')): ?>
                                    <button class="hd-delete-photo" data-id="<?php echo $photo->id; ?>" data-request-id="<?php echo $request->id; ?>" style="position: absolute; top: 4px; right: 4px; background: #e81123; color: white; border: none; border-radius: 4px; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px;">&times;</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="hd-detail-section" style="background: #fff; border: 1px dashed var(--win-border); padding: 16px; border-radius: var(--win-radius-sm);">
                <form id="hd-add-photo-form" style="display: flex; gap: 12px; align-items: center;">
                    <div style="flex: 1;">
                        <label style="font-size: 12px; font-weight: 700; color: var(--win-text-sec); display: block; margin-bottom: 4px;"><?php _e('Прикрепить новое фото', 'helpdesk-enterprise'); ?></label>
                        <input type="file" name="photo" accept="image/*" required class="hd-input" style="padding: 6px;">
                    </div>
                    <input type="hidden" name="request_id" value="<?php echo $request->id; ?>">
                    <button type="submit" class="hd-btn hd-btn-primary" style="align-self: flex-end;"><?php _e('Загрузить', 'helpdesk-enterprise'); ?></button>
                </form>
            </section>

            <section class="hd-detail-section">
                <h4 class="hd-detail-title" style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="message-square" style="width: 18px; height: 18px;"></i>
                    <?php _e('Обсуждение', 'helpdesk-enterprise'); ?>
                </h4>
                <div id="hd-comments-list" style="margin-bottom: 20px; max-height: 400px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; padding-right: 8px;">
                    <?php if (!$comments): ?>
                        <div style="text-align: center; padding: 40px; color: var(--win-text-sec); font-style: italic;">
                            <?php _e('Сообщений пока нет. Оставьте первый комментарий!', 'helpdesk-enterprise'); ?>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($comments as $comment): ?>
                        <div style="background: #f8f8f8; padding: 16px; border-radius: 12px; border: 1px solid var(--win-border); position: relative;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px;">
                                <span style="font-weight: 700; color: var(--win-accent);"><?php $c_user = HD_Auth::get_user_by_id($comment->user_id); echo $c_user ? $c_user->display_name : 'System'; ?></span>
                                <span style="color: var(--win-text-sec);"><?php echo date('d.m.Y H:i', strtotime($comment->created_at)); ?></span>
                            </div>
                            <div style="font-size: 14px; color: #333;"><?php echo nl2br(esc_html($comment->content)); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="hd-form-group">
                    <textarea id="hd-comment-content" class="hd-textarea" rows="3" placeholder="<?php _e('Напишите сообщение...', 'helpdesk-enterprise'); ?>" style="border-radius: 8px;"></textarea>
                    <button id="hd-submit-comment" data-id="<?php echo $request->id; ?>" class="hd-btn hd-btn-primary" style="margin-top: 12px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px;">
                        <i data-lucide="send" style="width: 16px; height: 16px;"></i>
                        <?php _e('Отправить сообщение', 'helpdesk-enterprise'); ?>
                    </button>
                </div>
            </section>
        </div>

        <div class="hd-side-col">
            <section class="hd-detail-section" style="background: #f3f3f3; padding: 20px; border-radius: var(--win-radius); border: 1px solid var(--win-border);">
                <h4 style="margin-top: 0; font-size: 13px; text-transform: uppercase; color: var(--win-text-sec); letter-spacing: 0.05em; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="info" style="width: 14px; height: 14px;"></i>
                    <?php _e('Системные данные', 'helpdesk-enterprise'); ?>
                </h4>
                <div style="font-size: 13px; display: grid; gap: 12px;">
                    <div><span style="color: var(--win-text-sec);"><?php _e('Заявитель:', 'helpdesk-enterprise'); ?></span><br><strong><?php $resp = HD_Auth::get_user_by_id($request->responsible_id); echo $resp ? $resp->display_name : 'Unknown'; ?></strong></div>
                    <div><span style="color: var(--win-text-sec);"><?php _e('Исполнитель:', 'helpdesk-enterprise'); ?></span><br><strong><?php $exec = $request->executor_id ? HD_Auth::get_user_by_id($request->executor_id) : null; echo $exec ? $exec->display_name : '<span style="color: #ef4444">Не назначен</span>'; ?></strong></div>
                    <?php
                        global $wpdb;
                        $cat_info = $wpdb->get_row($wpdb->prepare("SELECT priority FROM {$wpdb->prefix}hd_categories WHERE id = %d", $request->category_id));
                        if($cat_info):
                            $p_icons = array('low' => 'arrow-down', 'medium' => 'minus', 'high' => 'arrow-up', 'critical' => 'zap');
                            $p_icon = isset($p_icons[$cat_info->priority]) ? $p_icons[$cat_info->priority] : 'help-circle';
                    ?>
                        <div style="border-top: 1px solid var(--win-border); padding-top: 12px;">
                            <span style="color: var(--win-text-sec); font-size: 11px;"><?php _e('Приоритет:', 'helpdesk-enterprise'); ?></span><br>
                            <span style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; color: var(--win-accent);">
                                <i data-lucide="<?php echo $p_icon; ?>" style="width: 14px; height: 14px;"></i>
                                <?php echo strtoupper($cat_info->priority); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <div style="border-top: 1px solid var(--win-border); padding-top: 12px;"><span style="color: var(--win-text-sec);"><?php _e('Отдел:', 'helpdesk-enterprise'); ?></span><br><strong><?php echo esc_html($request->dept_name); ?></strong></div>
                    <div><span style="color: var(--win-text-sec);"><?php _e('Создана:', 'helpdesk-enterprise'); ?></span><br><strong><?php echo date('d.m.Y H:i', strtotime($request->created_at)); ?></strong></div>

                    <div style="background: #fff; padding: 12px; border-radius: 8px; border: 1px solid var(--win-border);">
                        <span style="color: var(--win-text-sec); font-size: 11px;"><?php _e('Контрольный срок (SLA):', 'helpdesk-enterprise'); ?></span><br>
                        <?php
                            $deadline_ts = strtotime($request->deadline);
                            $is_overdue = ($request->status !== 'completed' && $deadline_ts < current_time('timestamp'));
                        ?>
                        <strong style="font-size: 15px; <?php echo $is_overdue ? 'color: #ef4444;' : 'color: var(--win-accent);'; ?>">
                            <?php echo date('d.m.Y H:i', $deadline_ts); ?>
                        </strong>
                        <?php if ($is_overdue): ?>
                            <div style="color: #ef4444; font-size: 10px; font-weight: 700; margin-top: 4px; text-transform: uppercase;">
                                <i data-lucide="alert-triangle" style="width: 10px; height: 10px; display: inline-block;"></i> <?php _e('ПРОСРОЧЕНО', 'helpdesk-enterprise'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($request->completed_at): ?>
                        <div style="color: var(--hd-success); font-weight: 700;">
                            <span style="color: var(--win-text-sec); font-weight: 400;"><?php _e('Завершена:', 'helpdesk-enterprise'); ?></span><br>
                            <?php echo date('d.m.Y H:i', strtotime($request->completed_at)); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <?php if (HD_Auth::current_user_can('hd_update_status') || HD_Auth::current_user_can('hd_manage_dept') || HD_Auth::current_user_can('hd_manage_all')): ?>
                <section class="hd-detail-section" style="margin-top: 24px;">
                    <h4 class="hd-detail-title"><?php _e('Центр управления', 'helpdesk-enterprise'); ?></h4>
                    <div class="hd-form-group">
                        <label style="font-size: 12px;"><?php _e('Изменить статус', 'helpdesk-enterprise'); ?></label>
                        <select id="hd-status-change" data-id="<?php echo $request->id; ?>" class="hd-input">
                            <option value="new" <?php selected($request->status, 'new'); ?>>Новая</option>
                            <option value="in_progress" <?php selected($request->status, 'in_progress'); ?>>В работе</option>
                            <option value="pending" <?php selected($request->status, 'pending'); ?>>Ожидание</option>
                            <option value="completed" <?php selected($request->status, 'completed'); ?>>Выполнена</option>
                            <option value="rejected" <?php selected($request->status, 'rejected'); ?>>Отклонена</option>
                        </select>
                        <button id="hd-update-status-btn" class="hd-btn hd-btn-primary" style="margin-top: 8px; width: 100%;"><?php _e('Применить статус', 'helpdesk-enterprise'); ?></button>
                    </div>

                    <?php if (HD_Auth::current_user_can('hd_manage_dept') || HD_Auth::current_user_can('hd_manage_all')): ?>
                        <div class="hd-form-group" style="margin-top: 16px;">
                            <label style="font-size: 12px;"><?php _e('Назначить исполнителя', 'helpdesk-enterprise'); ?></label>
                            <select id="hd-executor-change" data-id="<?php echo $request->id; ?>" class="hd-input">
                                <option value="0"><?php _e('Выберите из списка...', 'helpdesk-enterprise'); ?></option>
                                <?php
                                global $wpdb;
                                $executors = $wpdb->get_results("SELECT id, display_name FROM {$wpdb->prefix}hd_users WHERE role IN ('hd_executor', 'hd_department_head', 'hd_administrator')");
                                foreach ($executors as $exec): ?>
                                    <option value="<?php echo $exec->id; ?>" <?php selected($request->executor_id, $exec->id); ?>><?php echo esc_html($exec->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button id="hd-assign-executor-btn" class="hd-btn hd-btn-primary" style="margin-top: 8px; width: 100%;"><?php _e('Назначить', 'helpdesk-enterprise'); ?></button>
                        </div>

                        <div class="hd-form-group" style="margin-top: 16px;">
                            <label style="font-size: 12px;"><?php _e('Продлить SLA дедлайн', 'helpdesk-enterprise'); ?></label>
                            <input type="datetime-local" id="hd-deadline-change" class="hd-input" value="<?php echo date('Y-m-d\TH:i', strtotime($request->deadline)); ?>">
                            <button id="hd-update-deadline-btn" data-id="<?php echo $request->id; ?>" class="hd-btn hd-btn-primary" style="margin-top: 8px; width: 100%;"><?php _e('Обновить срок', 'helpdesk-enterprise'); ?></button>
                        </div>

                        <?php if (HD_Auth::current_user_can('hd_delete_data')): ?>
                            <button id="hd-delete-request-btn" data-id="<?php echo $request->id; ?>" class="hd-btn" style="background: transparent; color: #ef4444; border: 1px solid #ef4444; width: 100%; margin-top: 24px; font-weight: 700;"><?php _e('Удалить заявку навсегда', 'helpdesk-enterprise'); ?></button>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="hd-detail-section">
                <h4 class="hd-detail-title"><?php _e('Журнал событий', 'helpdesk-enterprise'); ?></h4>
                <div style="background: #fff; border: 1px solid var(--win-border); border-radius: 8px; padding: 12px;">
                    <ul class="hd-history-list" style="margin: 0;">
                        <?php foreach (array_reverse($history) as $event): ?>
                            <li class="hd-history-item" style="padding: 10px 0; border-bottom: 1px solid #f1f5f9;">
                                <div class="hd-history-time" style="font-size: 10px; color: var(--win-text-sec); margin-bottom: 4px;"><?php echo date('d.m.Y H:i', strtotime($event->created_at)); ?></div>
                                <div style="font-weight: 600; font-size: 12px;">
                                    <?php
                                    $types = array(
                                        'status_changed' => __('Статус изменен', 'helpdesk-enterprise'),
                                        'executor_changed' => __('Исполнитель изменен', 'helpdesk-enterprise'),
                                        'deadline_changed' => __('Срок изменен', 'helpdesk-enterprise'),
                                        'comment_added' => __('Комментарий', 'helpdesk-enterprise'),
                                        'photo_added' => __('Загружено фото', 'helpdesk-enterprise'),
                                        'comment_deleted' => __('Удален комментарий', 'helpdesk-enterprise'),
                                        'photo_deleted' => __('Удалено фото', 'helpdesk-enterprise'),
                                        'request_created' => __('Создана заявка', 'helpdesk-enterprise'),
                                    );
                                    echo isset($types[$event->event_type]) ? $types[$event->event_type] : $event->event_type;
                                    ?>
                                </div>
                                <div style="font-size: 11px; color: var(--win-text-sec);">
                                    <?php if ($event->user_id): $e_user = HD_Auth::get_user_by_id($event->user_id); echo $e_user ? $e_user->display_name : 'Deleted User'; else: echo 'System'; endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</div>
<script>lucide.createIcons();</script>
