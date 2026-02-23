<?php include 'includes/header.php'; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="xp-card">
        <h3 style="color: #215DC6; margin-top: 0;">📍 Контактная информация</h3>
        <p style="font-size: 12px;"><strong>Адрес:</strong> 222514, РБ, Минская обл., Борисовский р-н, Пригородный с/с, 8, корп. 4</p>
        <p style="font-size: 12px;"><strong>Телефоны:</strong></p>
        <ul style="font-size: 11px; padding-left: 20px;">
            <li>Маркетолог: +375 (177) 92-99-69</li>
            <li>Регистратура: +375 (177) 74-86-33</li>
            <li>Приемная: +375 (177) 77-45-65</li>
        </ul>
        <p style="font-size: 11px;"><strong>Email:</strong> sanatorium@gu-berezina.by</p>
    </div>

    <div class="xp-card">
        <h3 style="color: #c13511; margin-top: 0;">📨 Напишите нам</h3>
        <form id="contactForm">
            <label style="display: block; font-size: 11px;">Имя:</label>
            <input type="text" class="form-control" style="width: 100%; margin-bottom: 10px; border: 1px solid #7F9DB9;">

            <label style="display: block; font-size: 11px;">Сообщение:</label>
            <textarea class="form-control" style="width: 100%; height: 60px; border: 1px solid #7F9DB9;"></textarea>

            <div style="text-align: right; margin-top: 10px;">
                <button type="submit" class="xp-btn">Отправить</button>
            </div>
        </form>
    </div>
</div>

<div class="xp-card">
    <h3 style="color: #215DC6;">🗺 Карта проезда</h3>
    <div style="border: 1px solid #7F9DB9; height: 300px;">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2334.862444585141!2d28.48710897654167!3d54.21808087255148!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x46da259972354567%3A0x8898b9a24422e0e!2z0YHQsNC90LDRgtC-0YDQuNC5IEJlcmV6aW5h!5e0!3m2!1sru!2sby!4v1700000000000!5m2!1sru!2sby" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
    </div>
    <p style="font-size: 11px; margin-top: 10px;">🚌 Проезд от автовокзала автобусом № 12 до ост. «Санаторий «Березина».</p>
</div>

<script>
document.getElementById('contactForm').onsubmit = function(e) {
    e.preventDefault();
    alert('Сообщение отправлено!');
    this.reset();
};
</script>

<?php include 'includes/footer.php'; ?>
