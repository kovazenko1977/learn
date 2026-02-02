<div class="wrap">
    <h1><?php _e('Settings', 'helpdesk-enterprise'); ?></h1>
    <form method="post" action="">
        <?php wp_nonce_field('hd_admin_action'); ?>
        <input type="hidden" name="hd_action" value="save_settings">

        <table class="form-table">
            <tr>
                <th><label for="telegram_token"><?php _e('Telegram Bot Token', 'helpdesk-enterprise'); ?></label></th>
                <td><input name="telegram_token" type="text" id="telegram_token" value="<?php echo esc_attr(get_option('hd_telegram_token')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="telegram_bot_name"><?php _e('Telegram Bot Name', 'helpdesk-enterprise'); ?></label></th>
                <td><input name="telegram_bot_name" type="text" id="telegram_bot_name" value="<?php echo esc_attr(get_option('hd_telegram_bot_name')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="telegram_admin_chat_id"><?php _e('Admin Telegram Chat ID', 'helpdesk-enterprise'); ?></label></th>
                <td><input name="telegram_admin_chat_id" type="text" id="telegram_admin_chat_id" value="<?php echo esc_attr(get_option('hd_telegram_admin_chat_id')); ?>" class="regular-text"></td>
            </tr>
        </table>
        <p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Save Settings', 'helpdesk-enterprise'); ?>"></p>
    </form>
</div>
