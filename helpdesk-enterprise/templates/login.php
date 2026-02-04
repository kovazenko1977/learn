<div class="hd-dashboard-wrapper" style="max-width: 400px; margin: 100px auto;">
    <header class="hd-header" style="justify-content: center; flex-direction: column;">
        <h2 class="hd-title" style="margin-bottom: 20px;"><?php _e('Вход в Helpdesk', 'helpdesk-enterprise'); ?></h2>
        <?php if (isset($_GET['logout'])): ?>
            <p style="color: var(--hd-success);"><?php _e('Вы успешно вышли из системы.', 'helpdesk-enterprise'); ?></p>
        <?php endif; ?>
    </header>

    <div class="hd-create-form-container">
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
            <button type="submit" class="hd-btn hd-btn-primary" style="width: 100%; padding: 12px;"><?php _e('Войти', 'helpdesk-enterprise'); ?></button>
        </form>
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
