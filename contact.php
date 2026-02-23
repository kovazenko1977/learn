<?php include 'includes/header.php'; ?>

<h1>Контакты</h1>

<div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
    <div class="card">
        <h3>Наш адрес</h3>
        <p>222514, Республика Беларусь, Минская область,<br>Борисовский р-н, Пригородный с/с, 8, корпус 4</p>

        <h3>Телефоны</h3>
        <ul style="list-style: none; padding: 0;">
            <li><strong>Приемная:</strong> 8(0177)77-45-65</li>
            <li><strong>Регистратура:</strong> 8(0177)74-86-33</li>
            <li><strong>Маркетолог:</strong> 8(0177)92-99-69</li>
            <li><strong>E-mail:</strong> sanatorium@gu-berezina.by</li>
        </ul>
    </div>

    <div class="card">
        <h3>Обратная связь</h3>
        <form id="contactForm">
            <div class="form-group">
                <label for="c_name">Ваше имя</label>
                <input type="text" id="c_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="c_phone">Телефон</label>
                <input type="tel" id="c_phone" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="c_msg">Сообщение</label>
                <textarea id="c_msg" class="form-control" rows="4"></textarea>
            </div>
            <button type="submit" class="btn">Отправить</button>
        </form>
    </div>
</div>

<div class="card">
    <h3>Мы на карте</h3>
    <div style="width: 100%; height: 400px; border-radius: 8px; overflow: hidden;">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2334.862444585141!2d28.48710897654167!3d54.21808087255148!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x46da259972354567%3A0x8898b9a24422e0e!2z0YHQsNC90LDRgtC-0YDQuNC5IEJlcmV6aW5h!5e0!3m2!1sru!2sby!4v1700000000000!5m2!1sru!2sby" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
    <p style="margin-top: 15px; font-size: 14px;"><strong>Как добраться:</strong> от автовокзала Борисова автобусом № 12; от железнодорожного вокзала автобусом № 1 до остановки «Санаторий «Березина».</p>
</div>

<script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Спасибо за ваше сообщение! Мы ответим вам в ближайшее время.');
    this.reset();
});
</script>

<?php include 'includes/footer.php'; ?>
