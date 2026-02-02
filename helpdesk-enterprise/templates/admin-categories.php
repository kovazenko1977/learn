<div class="wrap">
    <h1><?php _e('Categories', 'helpdesk-enterprise'); ?></h1>

    <div style="display: flex; gap: 20px;">
        <div style="flex: 1;">
            <h2><?php echo $edit_item ? __('Edit Category', 'helpdesk-enterprise') : __('Add New Category', 'helpdesk-enterprise'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('hd_admin_action'); ?>
                <input type="hidden" name="hd_action" value="save_category">
                <?php if ($edit_id): ?><input type="hidden" name="id" value="<?php echo $edit_id; ?>"><?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th><label for="name"><?php _e('Name', 'helpdesk-enterprise'); ?></label></th>
                        <td><input name="name" type="text" id="name" value="<?php echo $edit_item ? esc_attr($edit_item->name) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="department_id"><?php _e('Department', 'helpdesk-enterprise'); ?></label></th>
                        <td>
                            <select name="department_id" id="department_id" required>
                                <option value=""><?php _e('Select Department', 'helpdesk-enterprise'); ?></option>
                                <?php foreach ($depts as $dept): ?>
                                    <option value="<?php echo $dept->id; ?>" <?php selected($edit_item ? $edit_item->department_id : 0, $dept->id); ?>><?php echo esc_html($dept->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="base_sla"><?php _e('Base SLA (Hours)', 'helpdesk-enterprise'); ?></label></th>
                        <td><input name="base_sla" type="number" id="base_sla" value="<?php echo $edit_item ? esc_attr($edit_item->base_sla) : '24'; ?>" class="small-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="default_executor_id"><?php _e('Default Executor', 'helpdesk-enterprise'); ?></label></th>
                        <td>
                            <select name="default_executor_id" id="default_executor_id">
                                <option value="0"><?php _e('Select Executor', 'helpdesk-enterprise'); ?></option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user->ID; ?>" <?php selected($edit_item ? $edit_item->default_executor_id : 0, $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Save Category', 'helpdesk-enterprise'); ?>"></p>
                <?php if ($edit_id): ?>
                    <a href="?page=hd-categories"><?php _e('Cancel', 'helpdesk-enterprise'); ?></a>
                <?php endif; ?>
            </form>
        </div>

        <div style="flex: 1;">
            <h2><?php _e('All Categories', 'helpdesk-enterprise'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'helpdesk-enterprise'); ?></th>
                        <th><?php _e('Name', 'helpdesk-enterprise'); ?></th>
                        <th><?php _e('Department', 'helpdesk-enterprise'); ?></th>
                        <th><?php _e('SLA', 'helpdesk-enterprise'); ?></th>
                        <th><?php _e('Actions', 'helpdesk-enterprise'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo $item->id; ?></td>
                            <td><?php echo esc_html($item->name); ?></td>
                            <td><?php echo esc_html($item->dept_name); ?></td>
                            <td><?php echo $item->base_sla; ?>h</td>
                            <td>
                                <a href="?page=hd-categories&edit=<?php echo $item->id; ?>"><?php _e('Edit', 'helpdesk-enterprise'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
