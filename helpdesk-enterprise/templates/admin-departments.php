<div class="wrap">
    <h1><?php _e('Departments', 'helpdesk-enterprise'); ?></h1>

    <div style="display: flex; gap: 20px;">
        <div style="flex: 1;">
            <h2><?php echo $edit_item ? __('Edit Department', 'helpdesk-enterprise') : __('Add New Department', 'helpdesk-enterprise'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('hd_admin_action'); ?>
                <input type="hidden" name="hd_action" value="save_department">
                <?php if ($edit_id): ?><input type="hidden" name="id" value="<?php echo $edit_id; ?>"><?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th><label for="name"><?php _e('Name', 'helpdesk-enterprise'); ?></label></th>
                        <td><input name="name" type="text" id="name" value="<?php echo $edit_item ? esc_attr($edit_item->name) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="manager_id"><?php _e('Manager', 'helpdesk-enterprise'); ?></label></th>
                        <td>
                            <select name="manager_id" id="manager_id">
                                <option value="0"><?php _e('Select Manager', 'helpdesk-enterprise'); ?></option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user->ID; ?>" <?php selected($edit_item ? $edit_item->manager_id : 0, $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Working Hours', 'helpdesk-enterprise'); ?></label></th>
                        <td>
                            <input name="working_start" type="time" value="<?php echo isset($settings['working_hours']['start']) ? esc_attr($settings['working_hours']['start']) : '09:00'; ?>">
                            <?php _e('to', 'helpdesk-enterprise'); ?>
                            <input name="working_end" type="time" value="<?php echo isset($settings['working_hours']['end']) ? esc_attr($settings['working_hours']['end']) : '18:00'; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Weekends', 'helpdesk-enterprise'); ?></label></th>
                        <td>
                            <?php
                            $days = array(1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun');
                            $current_weekends = isset($settings['weekends']) ? $settings['weekends'] : array(0, 6);
                            foreach ($days as $val => $label): ?>
                                <label><input type="checkbox" name="weekends[]" value="<?php echo $val; ?>" <?php checked(in_array($val, $current_weekends)); ?>> <?php echo $label; ?></label><br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="holidays"><?php _e('Holidays (YYYY-MM-DD, one per line)', 'helpdesk-enterprise'); ?></label></th>
                        <td><textarea name="holidays" id="holidays" rows="5" class="large-text"><?php echo isset($settings['holidays']) ? esc_textarea(implode("\n", $settings['holidays'])) : ''; ?></textarea></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Save Department', 'helpdesk-enterprise'); ?>"></p>
                <?php if ($edit_id): ?>
                    <a href="?page=hd-departments"><?php _e('Cancel', 'helpdesk-enterprise'); ?></a>
                <?php endif; ?>
            </form>
        </div>

        <div style="flex: 1;">
            <h2><?php _e('All Departments', 'helpdesk-enterprise'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'helpdesk-enterprise'); ?></th>
                        <th><?php _e('Name', 'helpdesk-enterprise'); ?></th>
                        <th><?php _e('Manager', 'helpdesk-enterprise'); ?></th>
                        <th><?php _e('Actions', 'helpdesk-enterprise'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo $item->id; ?></td>
                            <td><?php echo esc_html($item->name); ?></td>
                            <td><?php $m = get_userdata($item->manager_id); echo $m ? esc_html($m->display_name) : '-'; ?></td>
                            <td>
                                <a href="?page=hd-departments&edit=<?php echo $item->id; ?>"><?php _e('Edit', 'helpdesk-enterprise'); ?></a> |
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('hd_admin_action'); ?>
                                    <input type="hidden" name="hd_action" value="delete_department">
                                    <input type="hidden" name="id" value="<?php echo $item->id; ?>">
                                    <input type="submit" class="button-link" value="<?php _e('Delete', 'helpdesk-enterprise'); ?>" onclick="return confirm('Are you sure?')">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
