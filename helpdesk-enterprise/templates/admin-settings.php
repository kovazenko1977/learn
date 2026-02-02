<div class="wrap">
    <h1><?php _e('Настройки', 'helpdesk-enterprise'); ?></h1>
    <form method="post" action="">
        <?php wp_nonce_field('hd_admin_action'); ?>
        <input type="hidden" name="hd_action" value="save_settings">

        <table class="form-table">
            <tr>
                <th><label for="company_name"><?php _e('Название компании', 'helpdesk-enterprise'); ?></label></th>
                <td><input name="company_name" type="text" id="company_name" value="<?php echo esc_attr(get_option('hd_company_name')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="telegram_token"><?php _e('Токен Telegram бота', 'helpdesk-enterprise'); ?></label></th>
                <td><input name="telegram_token" type="text" id="telegram_token" value="<?php echo esc_attr(get_option('hd_telegram_token')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="telegram_bot_name"><?php _e('Имя Telegram бота', 'helpdesk-enterprise'); ?></label></th>
                <td><input name="telegram_bot_name" type="text" id="telegram_bot_name" value="<?php echo esc_attr(get_option('hd_telegram_bot_name')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="telegram_admin_chat_id"><?php _e('ID чата администратора Telegram', 'helpdesk-enterprise'); ?></label></th>
                <td><input name="telegram_admin_chat_id" type="text" id="telegram_admin_chat_id" value="<?php echo esc_attr(get_option('hd_telegram_admin_chat_id')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label><?php _e('Уведомления', 'helpdesk-enterprise'); ?></label></th>
                <td>
                    <label><input type="checkbox" name="notify_on_create" value="1" <?php checked(get_option('hd_notify_on_create'), 1); ?>> <?php _e('Уведомлять о новых заявках', 'helpdesk-enterprise'); ?></label><br>
                    <label><input type="checkbox" name="notify_on_status" value="1" <?php checked(get_option('hd_notify_on_status'), 1); ?>> <?php _e('Уведомлять о смене статуса', 'helpdesk-enterprise'); ?></label>
                </td>
            </tr>
        </table>
        <p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Сохранить настройки', 'helpdesk-enterprise'); ?>"></p>
    </form>
</div>
