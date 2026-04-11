<div class="hd-admin-standalone">
    <h1 class="hd-title" style="margin-bottom: 24px;"><?php _e('Управление отделами', 'helpdesk-enterprise'); ?></h1>

    <div class="hd-request-grid">
        <div class="hd-main-col">
            <div class="hd-create-form-container">
                <h3 style="margin-top: 0;"><?php echo $edit_item ? __('Редактировать отдел', 'helpdesk-enterprise') : __('Добавить отдел', 'helpdesk-enterprise'); ?></h3>
                <form method="post" action="">
                    <?php wp_nonce_field('hd_admin_action'); ?>
                    <input type="hidden" name="hd_action" value="save_department">
                    <?php if ($edit_id): ?><input type="hidden" name="id" value="<?php echo $edit_id; ?>"><?php endif; ?>

                    <div class="hd-form-group">
                        <label for="name"><?php _e('Название отдела', 'helpdesk-enterprise'); ?></label>
                        <input name="name" type="text" id="name" value="<?php echo $edit_item ? esc_attr($edit_item->name) : ''; ?>" class="hd-input" required>
                    </div>

                    <div class="hd-form-group">
                        <label for="manager_id"><?php _e('Руководитель (из списка сотрудников)', 'helpdesk-enterprise'); ?></label>
                        <select name="manager_id" id="manager_id" class="hd-input">
                            <option value="0"><?php _e('Не назначен', 'helpdesk-enterprise'); ?></option>
                            <?php
                            global $wpdb;
                            $hd_users = $wpdb->get_results("SELECT id, display_name FROM {$wpdb->prefix}hd_users WHERE role = 'hd_department_head'");
                            foreach ($hd_users as $user): ?>
                                <option value="<?php echo $user->id; ?>" <?php selected($edit_item ? $edit_item->manager_id : 0, $user->id); ?>><?php echo esc_html($user->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="hd-form-group">
                            <label><?php _e('Рабочее время', 'helpdesk-enterprise'); ?></label>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input name="working_start" type="time" class="hd-input" value="<?php echo isset($settings['working_hours']['start']) ? esc_attr($settings['working_hours']['start']) : '09:00'; ?>">
                                <span>-</span>
                                <input name="working_end" type="time" class="hd-input" value="<?php echo isset($settings['working_hours']['end']) ? esc_attr($settings['working_hours']['end']) : '18:00'; ?>">
                            </div>
                        </div>
                        <div class="hd-form-group">
                            <label><?php _e('Обеденный перерыв', 'helpdesk-enterprise'); ?></label>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input name="lunch_start" type="time" class="hd-input" value="<?php echo isset($settings['lunch_break']['start']) ? esc_attr($settings['lunch_break']['start']) : '13:00'; ?>">
                                <span>-</span>
                                <input name="lunch_end" type="time" class="hd-input" value="<?php echo isset($settings['lunch_break']['end']) ? esc_attr($settings['lunch_break']['end']) : '14:00'; ?>">
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: center; margin-top: 12px;">
                        <button type="submit" class="hd-btn hd-btn-primary" style="flex: 1; padding: 12px;"><?php _e('Сохранить данные отдела', 'helpdesk-enterprise'); ?></button>
                        <?php if ($edit_id): ?>
                            <a href="<?php echo remove_query_arg('edit'); ?>" class="hd-btn" style="background: var(--hd-border);"><?php _e('Отмена', 'helpdesk-enterprise'); ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="hd-table-container">
                <table class="hd-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th><?php _e('Отдел', 'helpdesk-enterprise'); ?></th>
                            <th><?php _e('Руководитель', 'helpdesk-enterprise'); ?></th>
                            <th style="text-align: right;"><?php _e('Действия', 'helpdesk-enterprise'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>#<?php echo $item->id; ?></td>
                            <td style="font-weight: 600;"><?php echo esc_html($item->name); ?></td>
                            <td><?php $m = HD_Auth::get_user_by_id($item->manager_id); echo $m ? esc_html($m->display_name) : '<span style="color: var(--hd-secondary)">-</span>'; ?></td>
                            <td style="text-align: right;">
                                <a href="<?php echo add_query_arg('edit', $item->id); ?>" class="hd-btn" style="background: #f1f5f9; color: var(--hd-primary); padding: 4px 8px; font-size: 12px;"><?php _e('Правка', 'helpdesk-enterprise'); ?></a>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('hd_admin_action'); ?>
                                    <input type="hidden" name="hd_action" value="delete_department">
                                    <input type="hidden" name="id" value="<?php echo $item->id; ?>">
                                    <button type="submit" class="hd-btn" style="background: transparent; color: var(--hd-danger); padding: 4px; font-size: 12px;" onclick="return confirm('Удалить отдел?')"><?php _e('Удалить', 'helpdesk-enterprise'); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="hd-side-col">
            <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid var(--hd-border);">
                <h4 style="margin-top: 0;"><?php _e('График и Выходные', 'helpdesk-enterprise'); ?></h4>
                <div class="hd-form-group">
                    <label><?php _e('Выходные дни', 'helpdesk-enterprise'); ?></label>
                    <div style="font-size: 13px; display: grid; gap: 4px;">
                        <?php
                        $days = array(1 => 'Понедельник', 2 => 'Вторник', 3 => 'Среда', 4 => 'Четверг', 5 => 'Пятница', 6 => 'Суббота', 0 => 'Воскресенье');
                        $current_weekends = isset($settings['weekends']) ? $settings['weekends'] : array(0, 6);
                        foreach ($days as $val => $label): ?>
                            <label style="font-weight: 400;"><input type="checkbox" name="weekends[]" value="<?php echo $val; ?>" <?php checked(in_array($val, $current_weekends)); ?>> <?php echo $label; ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="hd-form-group" style="margin-top: 20px;">
                    <label for="holidays"><?php _e('Праздничные дни', 'helpdesk-enterprise'); ?></label>
                    <textarea name="holidays" id="holidays" rows="4" class="hd-textarea" placeholder="2026-01-01&#10;2026-05-09" style="font-size: 12px;"><?php echo isset($settings['holidays']) ? esc_textarea(implode("\n", $settings['holidays'])) : ''; ?></textarea>
                    <p style="font-size: 11px; color: var(--hd-secondary);"><?php _e('Формат ГГГГ-ММ-ДД, по одному на строку.', 'helpdesk-enterprise'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
