<div class="hd-request-detail">
    <h3><?php echo esc_html($request->title); ?> (#<?php echo $request->id; ?>)</h3>
    <div class="hd-meta">
        <p><strong><?php _e('Status:', 'helpdesk-enterprise'); ?></strong> <?php echo esc_html($request->status); ?></p>
        <p><strong><?php _e('Category:', 'helpdesk-enterprise'); ?></strong> <?php echo esc_html($request->cat_name); ?></p>
        <p><strong><?php _e('Department:', 'helpdesk-enterprise'); ?></strong> <?php echo esc_html($request->dept_name); ?></p>
        <p><strong><?php _e('Deadline:', 'helpdesk-enterprise'); ?></strong> <?php echo esc_html($request->deadline); ?></p>
    </div>

    <div class="hd-description">
        <h4><?php _e('Description', 'helpdesk-enterprise'); ?></h4>
        <p><?php echo wpautop(esc_html($request->description)); ?></p>
    </div>

    <?php if ($photos): ?>
        <div class="hd-photos">
            <h4><?php _e('Photos', 'helpdesk-enterprise'); ?></h4>
            <div class="hd-photo-gallery">
                <?php foreach ($photos as $photo): ?>
                    <div class="hd-photo-item">
                        <a href="<?php echo esc_url($photo->file_url); ?>" target="_blank">
                            <img src="<?php echo esc_url($photo->file_url); ?>" style="max-width: 150px; height: auto; margin: 5px;">
                        </a>
                        <?php if (current_user_can('hd_delete_data')): ?>
                            <button class="hd-delete-photo" data-id="<?php echo $photo->id; ?>" data-request-id="<?php echo $request->id; ?>">×</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (current_user_can('hd_update_status') || current_user_can('hd_manage_dept_requests') || current_user_can('hd_manage_all')): ?>
        <div class="hd-actions">
            <h4><?php _e('Actions', 'helpdesk-enterprise'); ?></h4>
            <select id="hd-status-change" data-id="<?php echo $request->id; ?>">
                <option value="new" <?php selected($request->status, 'new'); ?>>New</option>
                <option value="in_progress" <?php selected($request->status, 'in_progress'); ?>>In Progress</option>
                <option value="pending" <?php selected($request->status, 'pending'); ?>>Pending</option>
                <option value="completed" <?php selected($request->status, 'completed'); ?>>Completed</option>
                <option value="rejected" <?php selected($request->status, 'rejected'); ?>>Rejected</option>
            </select>
            <button id="hd-update-status-btn" class="button"><?php _e('Update Status', 'helpdesk-enterprise'); ?></button>
        </div>
    <?php endif; ?>

    <div class="hd-comments-section">
        <h4><?php _e('Comments', 'helpdesk-enterprise'); ?></h4>
        <div id="hd-comments-list">
            <?php foreach ($comments as $comment): ?>
                <div class="hd-comment">
                    <strong><?php echo get_userdata($comment->user_id)->display_name; ?>:</strong>
                    <span><?php echo esc_html($comment->content); ?></span>
                    <small>(<?php echo $comment->created_at; ?>)</small>
                </div>
            <?php endforeach; ?>
        </div>
        <textarea id="hd-comment-content" placeholder="<?php _e('Add a comment...', 'helpdesk-enterprise'); ?>"></textarea>
        <button id="hd-submit-comment" data-id="<?php echo $request->id; ?>" class="button"><?php _e('Add Comment', 'helpdesk-enterprise'); ?></button>
    </div>

    <div class="hd-history-section">
        <h4><?php _e('History', 'helpdesk-enterprise'); ?></h4>
        <ul class="hd-history-list">
            <?php foreach ($history as $event): ?>
                <li>
                    <strong><?php echo esc_html($event->event_type); ?></strong> by
                    <?php echo $event->user_id ? get_userdata($event->user_id)->display_name : 'System'; ?>
                    at <?php echo $event->created_at; ?>
                    <?php if ($event->old_value || $event->new_value): ?>
                        <br><small>
                            <?php if ($event->old_value): ?><i><?php _e('From:', 'helpdesk-enterprise'); ?></i> <?php echo esc_html($event->old_value); ?><?php endif; ?>
                            <?php if ($event->new_value): ?> <i><?php _e('To:', 'helpdesk-enterprise'); ?></i> <?php echo esc_html($event->new_value); ?><?php endif; ?>
                        </small>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
