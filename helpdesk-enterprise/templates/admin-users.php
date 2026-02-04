<div class="wrap">
    <h1><?php _e('Управление пользователями Helpdesk', 'helpdesk-enterprise'); ?></h1>

    <div style="display: flex; gap: 20px;">
        <div style="flex: 1;">
            <h2><?php echo $edit_item ? __('Редактировать пользователя', 'helpdesk-enterprise') : __('Добавить нового пользователя', 'helpdesk-enterprise'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('hd_admin_action'); ?>
                <input type="hidden" name="hd_action" value="save_user">
                <?php if ($edit_id): ?><input type="hidden" name="id" value="<?php echo $edit_id; ?>"><?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th><label for="username"><?php _e('Логин', 'helpdesk-enterprise'); ?></label></th>
                        <td><input name="username" type="text" id="username" value="<?php echo $edit_item ? esc_attr($edit_item->username) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="password"><?php _e('Пароль', 'helpdesk-enterprise'); ?></label></th>
                        <td>
                            <input name="password" type="password" id="password" class="regular-text" <?php echo $edit_item ? '' : 'required'; ?>>
                            <?php if ($edit_item): ?><p class="description"><?php _e('Оставьте пустым, чтобы не менять', 'helpdesk-enterprise'); ?></p><?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="email"><?php _e('Email', 'helpdesk-enterprise'); ?></label></th>
                        <td><input name="email" type="email" id="email" value="<?php echo $edit_item ? esc_attr($edit_item->email) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="display_name"><?php _e('Отображаемое имя', 'helpdesk-enterprise'); ?></label></th>
                        <td><input name="display_name" type="text" id="display_name" value="<?php echo $edit_item ? esc_attr($edit_item->display_name) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="role"><?php _e('Роль', 'helpdesk-enterprise'); ?></label></th>
                        <td>
                            <select name="role" id="role" required>
                                <option value="hd_responsible" <?php selected($edit_item ? $edit_item->role : '', 'hd_responsible'); ?>><?php _e('Ответственный', 'helpdesk-enterprise'); ?></option>
                                <option value="hd_executor" <?php selected($edit_item ? $edit_item->role : '', 'hd_executor'); ?>><?php _e('Исполнитель', 'helpdesk-enterprise'); ?></option>
                                <option value="hd_department_head" <?php selected($edit_item ? $edit_item->role : '', 'hd_department_head'); ?>><?php _e('Руководитель отдела', 'helpdesk-enterprise'); ?></option>
                                <option value="hd_administrator" <?php selected($edit_item ? $edit_item->role : '', 'hd_administrator'); ?>><?php _e('Администратор', 'helpdesk-enterprise'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Сохранить пользователя', 'helpdesk-enterprise'); ?>"></p>
                <?php if ($edit_id): ?>
                    <a href="?page=hd-users"><?php _e('Отмена', 'helpdesk-enterprise'); ?></a>
                <?php endif; ?>
            </form>
        </div>

        <div style="flex: 1;">
            <h2><?php _e('Список пользователей', 'helpdesk-enterprise'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'helpdesk-enterprise'); ?></th>
                        <th><?php _e('Логин', 'helpdesk-enterprise'); ?></th>
                        <th><?php _e('Имя', 'helpdesk-enterprise'); ?></th>
                        <th><?php _e('Роль', 'helpdesk-enterprise'); ?></th>
                        <th><?php _e('Действия', 'helpdesk-enterprise'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo $item->id; ?></td>
                            <td><?php echo esc_html($item->username); ?></td>
                            <td><?php echo esc_html($item->display_name); ?></td>
                            <td><?php echo esc_html($item->role); ?></td>
                            <td>
                                <a href="?page=hd-users&edit=<?php echo $item->id; ?>"><?php _e('Редактировать', 'helpdesk-enterprise'); ?></a> |
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('hd_admin_action'); ?>
                                    <input type="hidden" name="hd_action" value="delete_user">
                                    <input type="hidden" name="id" value="<?php echo $item->id; ?>">
                                    <input type="submit" class="button-link" value="<?php _e('Удалить', 'helpdesk-enterprise'); ?>" onclick="return confirm('Вы уверены?')">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
