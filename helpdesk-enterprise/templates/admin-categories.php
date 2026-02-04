<div class="wrap">
    <h1><?php _e('Категории', 'helpdesk-enterprise'); ?></h1>

    <div style="display: flex; gap: 20px;">
        <div style="flex: 1;">
            <h2><?php echo $edit_item ? __('Редактировать категорию', 'helpdesk-enterprise') : __('Добавить новую категорию', 'helpdesk-enterprise'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('hd_admin_action'); ?>
                <input type="hidden" name="hd_action" value="save_category">
                <?php if ($edit_id): ?><input type="hidden" name="id" value="<?php echo $edit_id; ?>"><?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th><label for="name"><?php _e('Название', 'helpdesk-enterprise'); ?></label></th>
                        <td><input name="name" type="text" id="name" value="<?php echo $edit_item ? esc_attr($edit_item->name) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="department_id"><?php _e('Отдел', 'helpdesk-enterprise'); ?></label></th>
                        <td>
                            <select name="department_id" id="department_id" required>
                                <option value=""><?php _e('Выберите отдел', 'helpdesk-enterprise'); ?></option>
                                <?php foreach ($depts as $dept): ?>
                                    <option value="<?php echo $dept->id; ?>" <?php selected($edit_item ? $edit_item->department_id : 0, $dept->id); ?>><?php echo esc_html($dept->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="base_sla"><?php _e('Базовый SLA (в часах)', 'helpdesk-enterprise'); ?></label></th>
                        <td><input name="base_sla" type="number" id="base_sla" value="<?php echo $edit_item ? esc_attr($edit_item->base_sla) : '24'; ?>" class="small-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="priority"><?php _e('Приоритет', 'helpdesk-enterprise'); ?></label></th>
                        <td>
                            <select name="priority" id="priority">
                                <option value="low" <?php selected($edit_item ? $edit_item->priority : '', 'low'); ?>><?php _e('Низкий', 'helpdesk-enterprise'); ?></option>
                                <option value="medium" <?php selected($edit_item ? $edit_item->priority : '', 'medium'); ?>><?php _e('Средний', 'helpdesk-enterprise'); ?></option>
                                <option value="high" <?php selected($edit_item ? $edit_item->priority : '', 'high'); ?>><?php _e('Высокий', 'helpdesk-enterprise'); ?></option>
                                <option value="critical" <?php selected($edit_item ? $edit_item->priority : '', 'critical'); ?>><?php _e('Критический', 'helpdesk-enterprise'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="default_executor_id"><?php _e('Исполнитель по умолчанию', 'helpdesk-enterprise'); ?></label></th>
                        <td>
                            <select name="default_executor_id" id="default_executor_id">
                                <option value="0"><?php _e('Выберите исполнителя', 'helpdesk-enterprise'); ?></option>
                                <?php
                                global $wpdb;
                                $hd_users = $wpdb->get_results("SELECT id, display_name FROM {$wpdb->prefix}hd_users WHERE role IN ('hd_executor', 'hd_department_head', 'hd_administrator')");
                                foreach ($hd_users as $user): ?>
                                    <option value="<?php echo $user->id; ?>" <?php selected($edit_item ? $edit_item->default_executor_id : 0, $user->id); ?>><?php echo esc_html($user->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Сохранить категорию', 'helpdesk-enterprise'); ?>"></p>
                <?php if ($edit_id): ?>
                    <a href="?page=hd-categories"><?php _e('Отмена', 'helpdesk-enterprise'); ?></a>
                <?php endif; ?>
            </form>
        </div>

        <div style="flex: 1;">
            <h2><?php _e('Все категории', 'helpdesk-enterprise'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'helpdesk-enterprise'); ?></th>
                        <th><?php _e('Название', 'helpdesk-enterprise'); ?></th>
                        <th><?php _e('Отдел', 'helpdesk-enterprise'); ?></th>
                        <th><?php _e('SLA', 'helpdesk-enterprise'); ?></th>
                        <th><?php _e('Действия', 'helpdesk-enterprise'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo $item->id; ?></td>
                            <td><?php echo esc_html($item->name); ?></td>
                            <td><?php echo esc_html($item->dept_name); ?></td>
                            <td><?php echo $item->base_sla; ?>ч</td>
                            <td>
                                <a href="?page=hd-categories&edit=<?php echo $item->id; ?>"><?php _e('Редактировать', 'helpdesk-enterprise'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
