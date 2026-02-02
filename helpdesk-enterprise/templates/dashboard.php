<div class="hd-dashboard">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2><?php _e('Helpdesk Dashboard', 'helpdesk-enterprise'); ?></h2>
        <div class="hd-user-settings">
            <form id="hd-user-settings-form" style="display: flex; gap: 10px; align-items: center;">
                <label><?php _e('Telegram Chat ID:', 'helpdesk-enterprise'); ?></label>
                <input type="text" name="telegram_chat_id" value="<?php echo esc_attr(get_user_meta(get_current_user_id(), 'hd_telegram_chat_id', true)); ?>" style="width: 120px;">
                <button type="submit" class="button"><?php _e('Save', 'helpdesk-enterprise'); ?></button>
            </form>
        </div>
    </div>

    <?php if (current_user_can('hd_create_requests')): ?>
        <section class="hd-create-request">
            <h3><?php _e('Create New Request', 'helpdesk-enterprise'); ?></h3>
            <form id="hd-create-form">
                <p>
                    <label><?php _e('Title', 'helpdesk-enterprise'); ?></label><br>
                    <input type="text" name="title" required>
                </p>
                <p>
                    <label><?php _e('Description', 'helpdesk-enterprise'); ?></label><br>
                    <textarea name="description" required></textarea>
                </p>
                <p>
                    <label><?php _e('Category', 'helpdesk-enterprise'); ?></label><br>
                    <select name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat->id; ?>"><?php echo esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>
                    <label><?php _e('Photo', 'helpdesk-enterprise'); ?></label><br>
                    <input type="file" name="photo" accept="image/*">
                </p>
                <button type="submit" class="button"><?php _e('Submit Request', 'helpdesk-enterprise'); ?></button>
            </form>
        </section>
    <?php endif; ?>

    <section class="hd-requests-list">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3><?php _e('Requests', 'helpdesk-enterprise'); ?></h3>
            <?php if (current_user_can('hd_export_dept') || current_user_can('hd_manage_all')): ?>
                <a href="<?php echo admin_url('admin-ajax.php?action=hd_export_requests&nonce=' . wp_create_nonce('hd_nonce')); ?>" class="button"><?php _e('Export to CSV', 'helpdesk-enterprise'); ?></a>
            <?php endif; ?>
        </div>
        <table class="hd-table">
            <thead>
                <tr>
                    <th><?php _e('ID', 'helpdesk-enterprise'); ?></th>
                    <th><?php _e('Title', 'helpdesk-enterprise'); ?></th>
                    <th><?php _e('Status', 'helpdesk-enterprise'); ?></th>
                    <th><?php _e('Department', 'helpdesk-enterprise'); ?></th>
                    <th><?php _e('Deadline', 'helpdesk-enterprise'); ?></th>
                    <th><?php _e('Actions', 'helpdesk-enterprise'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo $request->id; ?></td>
                        <td><?php echo esc_html($request->title); ?></td>
                        <td><span class="status-<?php echo $request->status; ?>"><?php echo esc_html($request->status); ?></span></td>
                        <td><?php echo esc_html($request->dept_name); ?></td>
                        <td><?php echo esc_html($request->deadline); ?></td>
                        <td>
                            <button class="hd-view-request" data-id="<?php echo $request->id; ?>"><?php _e('View', 'helpdesk-enterprise'); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <div id="hd-modal" style="display:none;">
        <div class="hd-modal-content">
            <span class="hd-close">&times;</span>
            <div id="hd-request-details"></div>
        </div>
    </div>
</div>
