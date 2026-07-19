<?php include 'includes/header.php'; ?>

<div class="window" style="max-width: 600px; margin: 40px auto;">
    <div class="title-bar">
        <div class="title-bar-text">Мастер бронирования</div>
        <div class="title-bar-controls">
            <button aria-label="Minimize">
                <svg width="10" height="1" viewBox="0 0 10 1"><path d="M0 0.5H10" stroke="currentColor" stroke-width="1"/></svg>
            </button>
            <button aria-label="Maximize">
                <svg width="10" height="10" viewBox="0 0 10 10"><rect x="0.5" y="0.5" width="9" height="9" fill="none" stroke="currentColor" stroke-width="1"/></svg>
            </button>
            <button aria-label="Close" onclick="window.location.href='index.php'">
                <svg width="10" height="10" viewBox="0 0 10 10"><path d="M1 1L9 9M9 1L1 9" stroke="currentColor" stroke-width="1.2"/></svg>
            </button>
        </div>
    </div>

    <div class="window-body" style="padding: 20px;">
        <h2 style="color: #215DC6; margin-top: 0; border-bottom: 1px solid #BDD2F1;">Бронирование путевки</h2>
        <p style="font-size: 11px;">Пожалуйста, заполните все обязательные поля (*).</p>

        <form id="bookingForm" method="POST" style="margin-top: 20px;">
            <div style="display: grid; grid-template-columns: 100px 1fr; gap: 15px; align-items: center;">
                <label style="font-size: 11px;">Ф.И.О. *</label>
                <input type="text" name="fio" required style="border: 1px solid #7F9DB9; padding: 2px;">

                <label style="font-size: 11px;">Дата заезда *</label>
                <input type="date" name="arrival_date" required style="border: 1px solid #7F9DB9; padding: 2px;">

                <label style="font-size: 11px;">Тип путевки</label>
                <select name="voucher_type" style="border: 1px solid #7F9DB9; padding: 2px;">
                    <option value="medical">Лечебно-оздоровительная (15 дн)</option>
                    <option value="wellness">Оздоровительная (15 дн)</option>
                    <option value="weekend">Тур выходного дня (2 дн)</option>
                </select>

                <label style="font-size: 11px;">Номер</label>
                <select name="room_category" style="border: 1px solid #7F9DB9; padding: 2px;">
                    <option value="single">Однокомнатный</option>
                    <option value="lux">Люкс</option>
                </select>

                <label style="font-size: 11px;">Телефон *</label>
                <input type="tel" name="phone" required placeholder="+375" style="border: 1px solid #7F9DB9; padding: 2px;">
            </div>

            <div style="margin-top: 20px; text-align: right; border-top: 1px solid #BDD2F1; padding-top: 15px;">
                <button type="submit" class="xp-btn xp-btn-primary" style="min-width: 80px;">Далее ></button>
                <button type="reset" class="xp-btn" style="min-width: 80px;">Отмена</button>
            </div>
        </form>

        <div id="formMessage" style="display: none; margin-top: 15px; padding: 10px; background: #DFF0D8; border: 1px solid #D6E9C6; color: #3C763D; font-size: 11px;">
            Заявка успешно отправлена!
            <button onclick="window.print()" style="margin-left: 10px; padding: 2px 5px; cursor: pointer;">Печать подтверждения</button>
        </div>
    </div>
</div>

<script>
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    document.getElementById('formMessage').style.display = 'block';
    this.reset();
});
</script>

<?php include 'includes/footer.php'; ?>
