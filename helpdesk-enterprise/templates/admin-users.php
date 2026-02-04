<div class="hd-admin-standalone">
    <h1 class="hd-title" style="margin-bottom: 24px;"><?php _e('Управление персоналом Helpdesk', 'helpdesk-enterprise'); ?></h1>

    <div class="hd-request-grid">
        <div class="hd-main-col">
            <div class="hd-create-form-container">
                <h3 style="margin-top: 0;"><?php echo $edit_item ? __('Редактировать пользователя', 'helpdesk-enterprise') : __('Добавить сотрудника', 'helpdesk-enterprise'); ?></h3>
                <form method="post" action="">
                    <?php wp_nonce_field('hd_admin_action'); ?>
                    <input type="hidden" name="hd_action" value="save_user">
                    <?php if ($edit_id): ?><input type="hidden" name="id" value="<?php echo $edit_id; ?>"><?php endif; ?>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="hd-form-group">
                            <label for="username"><?php _e('Логин', 'helpdesk-enterprise'); ?></label>
                            <input name="username" type="text" id="username" value="<?php echo $edit_item ? esc_attr($edit_item->username) : ''; ?>" class="hd-input" required>
                        </div>
                        <div class="hd-form-group">
                            <label for="password"><?php _e('Пароль', 'helpdesk-enterprise'); ?></label>
                            <input name="password" type="password" id="password" class="hd-input" <?php echo $edit_item ? '' : 'required'; ?>>
                            <?php if ($edit_item): ?><p style="font-size: 11px; color: var(--hd-secondary); margin: 4px 0 0;"><?php _e('Пусто = без изменений', 'helpdesk-enterprise'); ?></p><?php endif; ?>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="hd-form-group">
                            <label for="display_name"><?php _e('ФИО / Отображаемое имя', 'helpdesk-enterprise'); ?></label>
                            <input name="display_name" type="text" id="display_name" value="<?php echo $edit_item ? esc_attr($edit_item->display_name) : ''; ?>" class="hd-input" required>
                        </div>
                        <div class="hd-form-group">
                            <label for="phone"><?php _e('Номер телефона', 'helpdesk-enterprise'); ?></label>
                            <input name="phone" type="text" id="phone" value="<?php echo $edit_item ? esc_attr($edit_item->phone) : ''; ?>" class="hd-input" required placeholder="<?php _e('В свободном формате...', 'helpdesk-enterprise'); ?>">
                        </div>
                    </div>

                    <div class="hd-form-group">
                        <label for="role"><?php _e('Роль в системе', 'helpdesk-enterprise'); ?></label>
                        <select name="role" id="role" class="hd-input" required>
                            <option value="hd_responsible" <?php selected($edit_item ? $edit_item->role : '', 'hd_responsible'); ?>><?php _e('Ответственный сотрудник (Заявитель)', 'helpdesk-enterprise'); ?></option>
                            <option value="hd_executor" <?php selected($edit_item ? $edit_item->role : '', 'hd_executor'); ?>><?php _e('Исполнитель (Сотрудник поддержки)', 'helpdesk-enterprise'); ?></option>
                            <option value="hd_department_head" <?php selected($edit_item ? $edit_item->role : '', 'hd_department_head'); ?>><?php _e('Руководитель отдела', 'helpdesk-enterprise'); ?></option>
                            <option value="hd_administrator" <?php selected($edit_item ? $edit_item->role : '', 'hd_administrator'); ?>><?php _e('Администратор системы', 'helpdesk-enterprise'); ?></option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: center;">
                        <button type="submit" class="hd-btn hd-btn-primary" style="flex: 1; padding: 12px;"><?php _e('Сохранить пользователя', 'helpdesk-enterprise'); ?></button>
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
                            <th><?php _e('Сотрудник', 'helpdesk-enterprise'); ?></th>
                            <th><?php _e('Роль', 'helpdesk-enterprise'); ?></th>
                            <th style="text-align: right;"><?php _e('Действия', 'helpdesk-enterprise'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>#<?php echo $item->id; ?></td>
                            <td>
                                <div style="font-weight: 600;"><?php echo esc_html($item->display_name); ?></div>
                                <div style="font-size: 11px; color: var(--hd-secondary);"><?php echo esc_html($item->username); ?> | <?php echo esc_html($item->phone); ?></div>
                            </td>
                            <td><span class="hd-badge" style="background: #e2e8f0; color: #475569;"><?php echo esc_html($item->role); ?></span></td>
                            <td style="text-align: right;">
                                <a href="<?php echo add_query_arg('edit', $item->id); ?>" class="hd-btn" style="background: #f1f5f9; color: var(--hd-primary); padding: 4px 8px; font-size: 12px;"><?php _e('Правка', 'helpdesk-enterprise'); ?></a>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('hd_admin_action'); ?>
                                    <input type="hidden" name="hd_action" value="delete_user">
                                    <input type="hidden" name="id" value="<?php echo $item->id; ?>">
                                    <button type="submit" class="hd-btn" style="background: transparent; color: var(--hd-danger); padding: 4px; font-size: 12px;" onclick="return confirm('Удалить сотрудника?')"><?php _e('Удалить', 'helpdesk-enterprise'); ?></button>
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
                <h4 style="margin-top: 0;"><?php _e('Справка по ролям', 'helpdesk-enterprise'); ?></h4>
                <ul style="font-size: 13px; padding-left: 16px; margin: 0;">
                    <li><strong>Admin</strong>: Полный доступ.</li>
                    <li><strong>Head</strong>: Управление отделом и экспортом.</li>
                    <li><strong>Executor</strong>: Работа с заявками.</li>
                    <li><strong>Responsible</strong>: Создание заявок.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
