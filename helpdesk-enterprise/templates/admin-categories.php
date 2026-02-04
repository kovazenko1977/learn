<div class="hd-admin-standalone">
    <h1 class="hd-title" style="margin-bottom: 24px;"><?php _e('Классификация заявок (Категории)', 'helpdesk-enterprise'); ?></h1>

    <div class="hd-request-grid">
        <div class="hd-main-col">
            <div class="hd-create-form-container">
                <h3 style="margin-top: 0;"><?php echo $edit_item ? __('Редактировать категорию', 'helpdesk-enterprise') : __('Создать новую категорию', 'helpdesk-enterprise'); ?></h3>
                <form method="post" action="">
                    <?php wp_nonce_field('hd_admin_action'); ?>
                    <input type="hidden" name="hd_action" value="save_category">
                    <?php if ($edit_id): ?><input type="hidden" name="id" value="<?php echo $edit_id; ?>"><?php endif; ?>

                    <div class="hd-form-group">
                        <label for="name"><?php _e('Название категории', 'helpdesk-enterprise'); ?></label>
                        <input name="name" type="text" id="name" value="<?php echo $edit_item ? esc_attr($edit_item->name) : ''; ?>" class="hd-input" required>
                    </div>

                    <div class="hd-form-group">
                        <label for="department_id"><?php _e('Ответственный отдел', 'helpdesk-enterprise'); ?></label>
                        <select name="department_id" id="department_id" class="hd-input" required>
                            <option value=""><?php _e('Выберите отдел...', 'helpdesk-enterprise'); ?></option>
                            <?php foreach ($depts as $dept): ?>
                                <option value="<?php echo $dept->id; ?>" <?php selected($edit_item ? $edit_item->department_id : 0, $dept->id); ?>><?php echo esc_html($dept->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="hd-form-group">
                            <label for="base_sla"><?php _e('Базовый срок (часов)', 'helpdesk-enterprise'); ?></label>
                            <input name="base_sla" type="number" id="base_sla" value="<?php echo $edit_item ? esc_attr($edit_item->base_sla) : '24'; ?>" class="hd-input" required>
                        </div>
                        <div class="hd-form-group">
                            <label for="priority"><?php _e('Приоритет', 'helpdesk-enterprise'); ?></label>
                            <select name="priority" id="priority" class="hd-input">
                                <option value="low" <?php selected($edit_item ? $edit_item->priority : '', 'low'); ?>><?php _e('Низкий', 'helpdesk-enterprise'); ?></option>
                                <option value="medium" <?php selected($edit_item ? $edit_item->priority : '', 'medium'); ?>><?php _e('Средний', 'helpdesk-enterprise'); ?></option>
                                <option value="high" <?php selected($edit_item ? $edit_item->priority : '', 'high'); ?>><?php _e('Высокий', 'helpdesk-enterprise'); ?></option>
                                <option value="critical" <?php selected($edit_item ? $edit_item->priority : '', 'critical'); ?>><?php _e('Критический', 'helpdesk-enterprise'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="hd-form-group">
                        <label for="default_executor_id"><?php _e('Исполнитель по умолчанию', 'helpdesk-enterprise'); ?></label>
                        <select name="default_executor_id" id="default_executor_id" class="hd-input">
                            <option value="0"><?php _e('Авто-назначение отключено', 'helpdesk-enterprise'); ?></option>
                            <?php
                            global $wpdb;
                            $hd_users = $wpdb->get_results("SELECT id, display_name FROM {$wpdb->prefix}hd_users WHERE role IN ('hd_executor', 'hd_department_head', 'hd_administrator')");
                            foreach ($hd_users as $user): ?>
                                <option value="<?php echo $user->id; ?>" <?php selected($edit_item ? $edit_item->default_executor_id : 0, $user->id); ?>><?php echo esc_html($user->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: center;">
                        <button type="submit" class="hd-btn hd-btn-primary" style="flex: 1; padding: 12px;"><?php _e('Сохранить категорию', 'helpdesk-enterprise'); ?></button>
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
                            <th><?php _e('Категория', 'helpdesk-enterprise'); ?></th>
                            <th><?php _e('Отдел', 'helpdesk-enterprise'); ?></th>
                            <th><?php _e('SLA / Приоритет', 'helpdesk-enterprise'); ?></th>
                            <th style="text-align: right;"><?php _e('Действия', 'helpdesk-enterprise'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>#<?php echo $item->id; ?></td>
                            <td style="font-weight: 600;"><?php echo esc_html($item->name); ?></td>
                            <td><?php echo esc_html($item->dept_name); ?></td>
                            <td>
                                <div><?php echo $item->base_sla; ?> ч.</div>
                                <div style="font-size: 11px; color: var(--hd-secondary);"><?php echo esc_html($item->priority); ?></div>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?php echo add_query_arg('edit', $item->id); ?>" class="hd-btn" style="background: #f1f5f9; color: var(--hd-primary); padding: 4px 8px; font-size: 12px;"><?php _e('Правка', 'helpdesk-enterprise'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="hd-side-col">
            <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid var(--hd-border);">
                <h4 style="margin-top: 0;"><?php _e('Множители SLA', 'helpdesk-enterprise'); ?></h4>
                <ul style="font-size: 13px; padding-left: 16px; margin: 0; display: grid; gap: 8px;">
                    <li><strong>Low</strong>: x1.5 времени</li>
                    <li><strong>Medium</strong>: x1.0 времени</li>
                    <li><strong>High</strong>: x0.5 времени</li>
                    <li><strong>Critical</strong>: x0.25 времени</li>
                </ul>
                <p style="font-size: 12px; color: var(--hd-secondary); margin-top: 16px;"><?php _e('Базовое время умножается на коэффициент приоритета для расчета финального дедлайна.', 'helpdesk-enterprise'); ?></p>
            </div>
        </div>
    </div>
</div>
