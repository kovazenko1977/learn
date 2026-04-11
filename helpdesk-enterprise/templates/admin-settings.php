<div class="hd-admin-standalone">
    <h1 class="hd-title" style="margin-bottom: 24px;"><?php _e('Глобальные настройки системы', 'helpdesk-enterprise'); ?></h1>

    <div class="hd-create-form-container">
        <form method="post" action="">
            <?php wp_nonce_field('hd_admin_action'); ?>
            <input type="hidden" name="hd_action" value="save_settings">

            <div class="hd-form-group">
                <label for="company_name"><?php _e('Название компании', 'helpdesk-enterprise'); ?></label>
                <input name="company_name" type="text" id="company_name" value="<?php echo esc_attr(get_option('hd_company_name')); ?>" class="hd-input">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="hd-form-group">
                    <label for="telegram_token"><?php _e('Токен Telegram бота', 'helpdesk-enterprise'); ?></label>
                    <input name="telegram_token" type="text" id="telegram_token" value="<?php echo esc_attr(get_option('hd_telegram_token')); ?>" class="hd-input">
                </div>
                <div class="hd-form-group">
                    <label for="telegram_bot_name"><?php _e('Имя Telegram бота', 'helpdesk-enterprise'); ?></label>
                    <input name="telegram_bot_name" type="text" id="telegram_bot_name" value="<?php echo esc_attr(get_option('hd_telegram_bot_name')); ?>" class="hd-input">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="hd-form-group">
                    <label for="telegram_admin_chat_id"><?php _e('ID чата администратора Telegram', 'helpdesk-enterprise'); ?></label>
                    <input name="telegram_admin_chat_id" type="text" id="telegram_admin_chat_id" value="<?php echo esc_attr(get_option('hd_telegram_admin_chat_id')); ?>" class="hd-input">
                </div>
                <div class="hd-form-group">
                    <label for="default_sla"><?php _e('Дефолтный SLA (часов)', 'helpdesk-enterprise'); ?></label>
                    <input name="default_sla" type="number" id="default_sla" value="<?php echo esc_attr(get_option('hd_default_sla', 24)); ?>" class="hd-input">
                </div>
            </div>

            <div class="hd-form-group" style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <label style="margin-bottom: 16px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;"><?php _e('Системные уведомления', 'helpdesk-enterprise'); ?></label>
                <div style="display: flex; gap: 24px;">
                    <label style="font-weight: 400;"><input type="checkbox" name="notify_on_create" value="1" <?php checked(get_option('hd_notify_on_create'), 1); ?>> <?php _e('О новых заявках', 'helpdesk-enterprise'); ?></label>
                    <label style="font-weight: 400;"><input type="checkbox" name="notify_on_status" value="1" <?php checked(get_option('hd_notify_on_status'), 1); ?>> <?php _e('О смене статуса', 'helpdesk-enterprise'); ?></label>
                </div>
            </div>

            <button type="submit" class="hd-btn hd-btn-primary" style="width: 100%; padding: 14px; font-size: 16px;"><?php _e('Сохранить изменения', 'helpdesk-enterprise'); ?></button>
        </form>
    </div>

    <div class="hd-create-form-container" style="margin-top: 40px; border: 1px solid #fee2e2; background: #fffcfc;">
        <h3 style="color: var(--hd-danger); margin-top: 0;"><?php _e('Опасная зона: Полное обнуление (v7.0)', 'helpdesk-enterprise'); ?></h3>
        <p style="font-size: 14px; color: #64748b;"><?php _e('Данная функция ПОЛНОСТЬЮ удаляет все таблицы базы данных (включая пользователей, отделы и настройки) и пересоздает их заново. Все данные будут стерты навсегда.', 'helpdesk-enterprise'); ?></p>

        <div id="hd-reset-progress-container" style="display: none; margin-bottom: 20px; background: #f1f5f9; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span id="hd-reset-status" style="font-weight: 600; font-size: 14px; color: var(--hd-primary);">Подготовка...</span>
                <span id="hd-reset-percent" style="font-weight: 700; font-size: 14px;">0%</span>
            </div>
            <div style="width: 100%; height: 10px; background: #cbd5e1; border-radius: 5px; overflow: hidden;">
                <div id="hd-reset-bar" style="width: 0%; height: 100%; background: var(--hd-primary); transition: width 0.3s ease;"></div>
            </div>
        </div>

        <button id="hd-full-reset-btn" class="hd-btn" style="background: var(--hd-danger); color: white; padding: 14px 28px; font-weight: 800; border: none; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-warning" style="margin-top: 2px;"></span>
            <?php _e('ПОЛНОЕ ОБНУЛЕНИЕ И ПЕРЕСОЗДАНИЕ БД', 'helpdesk-enterprise'); ?>
        </button>
    </div>
</div>
