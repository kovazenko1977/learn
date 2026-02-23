<?php include 'includes/header.php'; ?>

<div class="hero card" style="background-image: linear-gradient(rgba(255,255,255,0.7), rgba(255,255,255,0.7)), url('https://gu-berezina.by/wp-content/uploads/2025/12/e1zb4ekmx5rmw0cvc7wrec3d10-1024x578.png'); background-size: cover; background-position: center; min-height: 400px; display: flex; align-items: center; justify-content: center; text-align: center;">
    <div>
        <h1 style="font-size: 3rem; color: var(--accent-color);">Добро пожаловать в Березину</h1>
        <p style="font-size: 1.2rem; max-width: 600px; margin: 0 auto;">Место, где забота о здоровье сочетается с уютом, вниманием и теплом.</p>
        <br>
        <a href="booking.php" class="btn" style="font-size: 1.1rem; padding: 12px 30px;">Забронировать отдых</a>
    </div>
</div>

<div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
    <div class="card">
        <h3>О нашем санатории</h3>
        <p>Санаторий расположен среди живописной природы Борисовщины на берегу реки Березина. 11 гектаров благоустроенной территории, окруженной сосновым лесом, дарят чистый воздух и спокойствие.</p>
        <p><strong>Главный врач:</strong> Шапель Юлия Владиславовна</p>
    </div>

    <div class="card">
        <h3>Почему выбирают нас?</h3>
        <ul>
            <li>Современные 15-дневные программы оздоровления</li>
            <li>Уникальный хвойный микроклимат</li>
            <li>Профессиональный медицинский персонал</li>
            <li>Комфортабельные номера различных категорий</li>
        </ul>
    </div>

    <div class="card">
        <h3>Экспресс-диагностика</h3>
        <p>Пройдите наш онлайн-тест, чтобы получить предварительные рекомендации по лечебным процедурам.</p>
        <a href="diagnostics.php" class="btn">Начать тест</a>
    </div>
</div>

<div class="card">
    <h2>Наши программы</h2>
    <p>Мы разработали комплексы процедур, которые дают кумулятивный эффект при пребывании от 15 дней. Именно за это время организм успевает перестроиться на здоровый ритм.</p>
    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
        <div style="flex: 1; min-width: 200px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center;">
            <h4>Лечебно-оздоровительная</h4>
            <p>15 дней</p>
        </div>
        <div style="flex: 1; min-width: 200px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center;">
            <h4>Оздоровительная</h4>
            <p>15 дней</p>
        </div>
        <div style="flex: 1; min-width: 200px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center;">
            <h4>Тур выходного дня</h4>
            <p>2-3 дня</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
