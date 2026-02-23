<?php include 'includes/header.php'; ?>

<h1>Бронирование путёвки</h1>
<p>Заполните форму ниже, и наши специалисты свяжутся с вами для подтверждения бронирования.</p>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form id="bookingForm" method="POST">
        <div class="form-group">
            <label for="fio">Ф.И.О. *</label>
            <input type="text" id="fio" name="fio" class="form-control" required placeholder="Введите ваше полное имя">
        </div>

        <div class="form-group">
            <label for="arrival_date">Дата заезда *</label>
            <input type="date" id="arrival_date" name="arrival_date" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="voucher_type">Вариант путевки</label>
            <select id="voucher_type" name="voucher_type" class="form-control">
                <option value="medical">Путевки с лечебно-оздоровительными процедурами (15 дн)</option>
                <option value="wellness">Путевки с оздоровительными процедурами (15 дн)</option>
                <option value="weekend">Тур выходного дня (2-3 дн)</option>
                <option value="custom">Произвольный выбор</option>
            </select>
        </div>

        <div class="form-group">
            <label for="room_category">Категория номера</label>
            <select id="room_category" name="room_category" class="form-control">
                <option value="single">Двухместный однокомнатный номер</option>
                <option value="lux2">Двухкомнатный двухместный номер «ЛЮКС»</option>
                <option value="lux3">Трехкомнатный двухместный номер «ЛЮКС»</option>
            </select>
        </div>

        <div class="form-group">
            <label for="days_count">Количество дней</label>
            <input type="number" id="days_count" name="days_count" class="form-control" min="1" value="15">
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" id="is_resident" name="is_resident" value="yes" checked>
                Резидент Республики Беларусь
            </label>
        </div>

        <div class="form-group">
            <label for="phone">Контактный телефон *</label>
            <input type="tel" id="phone" name="phone" class="form-control" required placeholder="+375 (__) ___-__-__">
        </div>

        <div id="formMessage" style="margin-bottom: 20px; padding: 10px; border-radius: 4px; display: none;"></div>

        <button type="submit" class="btn" style="width: 100%; font-size: 16px;">Забронировать</button>
    </form>
</div>

<script>
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const msg = document.getElementById('formMessage');
    msg.style.display = 'block';
    msg.style.backgroundColor = '#d4edda';
    msg.style.color = '#155724';
    msg.textContent = 'Спасибо! Ваша заявка принята. Мы свяжемся с вами в ближайшее время.';
    this.reset();

    // Smooth scroll to message
    msg.scrollIntoView({ behavior: 'smooth', block: 'center' });
});
</script>

<?php include 'includes/footer.php'; ?>
