<div class="hd-dashboard-wrapper" style="max-width: 420px; margin: 80px auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid var(--hd-border); background: #fff; border-radius: 16px; overflow: hidden; padding: 0;">
    <div style="background: var(--hd-primary); padding: 40px 20px; text-align: center; color: #fff;">
        <h2 class="hd-title" style="margin-bottom: 8px; color: #fff; font-size: 28px;"><?php _e('Helpdesk Enterprise', 'helpdesk-enterprise'); ?></h2>
        <p style="color: rgba(255,255,255,0.8); font-size: 14px; margin: 0;"><?php _e('Авторизация в системе v6.0', 'helpdesk-enterprise'); ?></p>
    </div>

    <div style="padding: 32px;">
        <?php if (isset($_GET['logout'])): ?>
            <div style="background: #f0fdf4; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center; border: 1px solid #dcfce7;">
                <?php _e('Вы успешно вышли из системы.', 'helpdesk-enterprise'); ?>
            </div>
        <?php endif; ?>

        <div class="hd-create-form-container" style="border: none; padding: 0; margin: 0; box-shadow: none;">
        <form id="hd-login-form">
            <div class="hd-form-group">
                <label><?php _e('Логин', 'helpdesk-enterprise'); ?></label>
                <input type="text" name="username" class="hd-input" required autofocus>
            </div>
            <div class="hd-form-group">
                <label><?php _e('Пароль', 'helpdesk-enterprise'); ?></label>
                <input type="password" name="password" class="hd-input" required>
            </div>
            <div id="hd-login-error" style="color: var(--hd-danger); margin-bottom: 10px; display: none; font-size: 14px;"></div>
            <button type="submit" class="hd-btn hd-btn-primary" style="width: 100%; padding: 14px; font-size: 16px; letter-spacing: 0.025em;"><?php _e('Войти в кабинет', 'helpdesk-enterprise'); ?></button>
        </form>
    </div>
    <div style="padding: 20px; text-align: center; background: #f8fafc; border-top: 1px solid #f1f5f9;">
        <p style="margin: 0; font-size: 12px; color: #94a3b8;"><?php echo sprintf(__('Разработчик: %s', 'helpdesk-enterprise'), 'Kovazenko S.B.'); ?></p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#hd-login-form').on('submit', function(e) {
        e.preventDefault();
        var data = $(this).serialize() + '&action=hd_login';

        $.post(hd_vars.ajax_url, data, function(response) {
            if (response.success) {
                location.reload();
            } else {
                $('#hd-login-error').text(response.data).show();
            }
        });
    });
});
</script>
